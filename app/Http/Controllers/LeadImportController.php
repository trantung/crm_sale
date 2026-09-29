<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Services\LeadImportService;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LeadImportController extends Controller
{
    public function __construct(
        private LeadImportService $importer,
        private LeadService $leads,
    ) {
        $this->middleware('can:import,'.Lead::class);
    }

    public function create(): View
    {
        return view('leads.import');
    }

    public function sample(): BinaryFileResponse
    {
        $path = public_path('samples/lead.xlsx');
        abort_unless(is_file($path), 404);

        return response()->download($path, 'lead.xlsx');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ]);

        $rows = $this->importer->parseFile($request->file('file'));
        if ($rows === []) {
            return back()->withErrors(['file' => 'Không đọc được dòng lead nào. Kiểm tra tiêu đề: STT, Name, Phone, Email, UTM.']);
        }

        $old = $request->session()->pull(LeadImportService::SESSION_KEY);
        if (is_string($old)) {
            $this->importer->forgetDraft($old);
        }

        $token = $this->importer->storeDraft($rows);
        $request->session()->put(LeadImportService::SESSION_KEY, $token);

        return redirect()->route('leads.import.preview')->with('status', 'Đã đọc '.count($rows).' dòng. Kiểm tra và sửa trước khi import.');
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $token = $request->session()->get(LeadImportService::SESSION_KEY);
        $rows = is_string($token) ? $this->importer->loadDraft($token) : [];
        if ($rows === []) {
            return redirect()->route('leads.import')->withErrors(['file' => 'Chưa có dữ liệu preview. Hãy upload file Excel.']);
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = LeadImportService::PER_PAGE;
        $slice = array_slice($rows, ($page - 1) * $perPage, $perPage, true);
        $paginator = new LengthAwarePaginator(
            $slice,
            count($rows),
            $perPage,
            $page,
            ['path' => route('leads.import.preview'), 'query' => $request->query()]
        );

        return view('leads.import-preview', [
            'rows' => $slice,
            'paginator' => $paginator,
            'total' => count($rows),
            'sources' => LeadSource::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function savePreview(Request $request): RedirectResponse
    {
        $token = $request->session()->get(LeadImportService::SESSION_KEY);
        if (! is_string($token) || $this->importer->loadDraft($token) === []) {
            return redirect()->route('leads.import')->withErrors(['file' => 'Phiên import đã hết hạn.']);
        }

        $updates = $request->input('rows', []);
        if (is_array($updates)) {
            $this->importer->mergeDraft($token, $updates);
        }

        $next = max(1, (int) $request->input('next_page', $request->input('page', 1)));

        return redirect()
            ->route('leads.import.preview', ['page' => $next])
            ->with('status', 'Đã lưu chỉnh sửa trang này.');
    }

    public function commit(Request $request): RedirectResponse
    {
        $token = $request->session()->get(LeadImportService::SESSION_KEY);
        if (! is_string($token)) {
            return redirect()->route('leads.import')->withErrors(['file' => 'Phiên import đã hết hạn.']);
        }

        $updates = $request->input('rows', []);
        $rows = is_array($updates)
            ? $this->importer->mergeDraft($token, $updates)
            : $this->importer->loadDraft($token);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $duplicates = [];
        $user = $request->user();

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                $skipped++;
                continue;
            }

            $utm = $this->importer->resolveUtm($row['utm'] ?? null);
            try {
                $result = $this->leads->ingest([
                    'name' => $name,
                    'phone' => $row['phone'] ?? '',
                    'email' => $row['email'] ?? '',
                    'allow_name_only' => true,
                    'source_code' => $utm['source_code'],
                    'utm_campaign' => $utm['utm_campaign'],
                    'utm_source' => $utm['utm_source'],
                ], 'crm', $user);

                if ($result['created']) {
                    $created++;
                } else {
                    $updated++;
                    $existing = $result['lead'];
                    $duplicates[] = ($existing->code ?: 'LD-'.$existing->id).' · '.($existing->phone_normalized ?: ($row['phone'] ?? ''));
                }
            } catch (\Throwable) {
                $skipped++;
            }
        }

        $this->importer->forgetDraft($token);
        $request->session()->forget(LeadImportService::SESSION_KEY);

        $message = "Import xong: {$created} lead mới, {$updated} lead trùng SĐT/email, {$skipped} dòng bỏ qua.";
        if ($duplicates) {
            $message .= ' Trùng: '.implode('; ', array_slice($duplicates, 0, 8));
            if (count($duplicates) > 8) {
                $message .= '…';
            }
        }

        return redirect()->route('leads.index')->with('status', $message);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $token = $request->session()->pull(LeadImportService::SESSION_KEY);
        if (is_string($token)) {
            $this->importer->forgetDraft($token);
        }

        return redirect()->route('leads.import')->with('status', 'Đã hủy bản preview import.');
    }
}
