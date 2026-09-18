<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadSource;
use App\Models\LeadStage;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct(private LeadService $leads)
    {
        $this->authorizeResource(Lead::class, 'lead');
        $this->middleware('can:create,'.Lead::class)->only(['importForm', 'importStore']);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Lead::query()
            ->with(['source', 'stage', 'owner', 'lastTouch'])
            ->visibleTo($user)
            ->orderByDesc('id');

        if ($q = trim((string) $request->input('q', ''))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%')
                    ->orWhere('phone_normalized', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('code', 'like', '%'.$q.'%');
            });
        }

        $tab = (string) $request->input('tab', '');
        if (in_array($tab, ['new', 'callback', 'recare'], true)) {
            $query->tab($tab);
        }

        if ($request->filled('stage_id')) {
            $query->where('stage_id', (int) $request->input('stage_id'));
        }

        if ($request->filled('source_id')) {
            $query->where('source_id', (int) $request->input('source_id'));
        }

        if ($user->isAdmin() && $request->filled('owner_id')) {
            if ($request->input('owner_id') === 'unassigned') {
                $query->whereNull('owner_id');
            } else {
                $query->where('owner_id', (int) $request->input('owner_id'));
            }
        }

        if ($campaign = trim((string) $request->input('utm_campaign', ''))) {
            $query->whereHas('lastTouch', fn ($t) => $t->where('utm_campaign', 'like', '%'.$campaign.'%'));
        }

        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [20, 50, 100], true)) {
            $perPage = 20;
        }

        $leads = $query->paginate($perPage)->withQueryString();

        $funnelQuery = Lead::query()->visibleTo($user);
        $tabCounts = [
            'new' => (clone $funnelQuery)->tab('new')->count(),
            'callback' => (clone $funnelQuery)->tab('callback')->count(),
            'recare' => (clone $funnelQuery)->tab('recare')->count(),
        ];
        $funnel = LeadStage::query()
            ->orderBy('sort_order')
            ->get()
            ->map(function (LeadStage $stage) use ($funnelQuery) {
                return [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'count' => (clone $funnelQuery)->where('stage_id', $stage->id)->count(),
                ];
            });

        return view('leads.index', [
            'leads' => $leads,
            'funnel' => $funnel,
            'stages' => LeadStage::query()->orderBy('sort_order')->get(),
            'sources' => LeadSource::query()->orderBy('sort_order')->get(),
            'sales' => $user->isAdmin()
                ? User::query()->where('role', User::ROLE_SALE)->orderBy('name')->get()
                : collect(),
            'tabCounts' => $tabCounts,
            'filters' => $request->only(['q', 'stage_id', 'source_id', 'owner_id', 'utm_campaign', 'tab', 'per_page']),
            'unassignedCount' => $user->isAdmin()
                ? Lead::query()->whereNull('owner_id')->count()
                : 0,
            'callResults' => Lead::CALL_RESULTS,
        ]);
    }

    public function create(): View
    {
        return view('leads.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);
        $user = $request->user();

        if ($user->isSale()) {
            unset($data['owner_id']);
        }

        $result = $this->leads->ingest($data, 'crm', $user);

        return redirect()
            ->route('leads.show', $result['lead'])
            ->with('status', $result['created'] ? 'Đã tạo lead.' : 'Lead đã tồn tại — đã ghi lần chạm mới.');
    }

    public function show(Lead $lead): View
    {
        $lead->load([
            'source',
            'stage',
            'owner',
            'creator',
            'firstTouch',
            'lastTouch',
            'leadTouches',
            'stageHistories.fromStage',
            'stageHistories.toStage',
            'stageHistories.changer',
            'activities.user',
        ]);

        return view('leads.show', [
            'lead' => $lead,
            'stages' => LeadStage::query()->orderBy('sort_order')->get(),
            'sales' => request()->user()->isAdmin()
                ? User::query()->where('role', User::ROLE_SALE)->where('is_active', true)->orderBy('name')->get()
                : collect(),
            'activityTypes' => LeadActivity::TYPES,
            'callResults' => Lead::CALL_RESULTS,
        ]);
    }

    public function edit(Lead $lead): View
    {
        return view('leads.edit', array_merge($this->formData(), ['lead' => $lead]));
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $data = $this->validated($request, false);

        $lead->fill([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'phone_normalized' => LeadService::normalizePhone($data['phone'] ?? null),
            'email' => $data['email'] ?? null,
            'sso_id' => $data['sso_id'] ?? null,
            'source_id' => $data['source_id'],
            'interested_product' => $data['interested_product'] ?? null,
            'note' => $data['note'] ?? null,
        ])->save();

        return redirect()->route('leads.show', $lead)->with('status', 'Đã cập nhật lead.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('leads.index')->with('status', 'Đã xóa lead.');
    }

    public function changeStage(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $data = $request->validate([
            'stage_id' => ['required', 'exists:lead_stages,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->leads->changeStage($lead, (int) $data['stage_id'], $request->user(), $data['reason'] ?? null);

        return back()->with('status', 'Đã chuyển trạng thái lead.');
    }

    public function assign(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('assign', $lead);

        $data = $request->validate([
            'owner_id' => ['nullable', 'exists:users,id'],
        ]);

        $this->leads->assign($lead, $data['owner_id'] ? (int) $data['owner_id'] : null, $request->user());

        return back()->with('status', 'Đã cập nhật người phụ trách.');
    }

    public function storeActivity(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $data = $request->validate([
            'type' => ['required', 'in:note,call,chat,meeting'],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        LeadActivity::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'content' => $data['content'],
        ]);

        return back()->with('status', 'Đã ghi hoạt động.');
    }

    public function logCall(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $data = $request->validate([
            'call_result' => ['required', 'in:'.implode(',', array_keys(Lead::CALL_RESULTS))],
            'callback_at' => ['nullable', 'date'],
            'content' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->leads->logCall(
            $lead,
            $request->user(),
            $data['call_result'],
            $data['content'] ?? null,
            $data['callback_at'] ?? null
        );

        return back()->with('status', 'Đã ghi kết quả cuộc gọi.');
    }

    public function importForm(): View
    {
        $this->authorize('create', Lead::class);

        return view('leads.import');
    }

    public function importStore(Request $request): RedirectResponse
    {
        $this->authorize('create', Lead::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if (! $handle) {
            return back()->withErrors(['file' => 'Không đọc được file.']);
        }

        $header = fgetcsv($handle);
        $created = 0;
        $updated = 0;
        $user = $request->user();

        while (($row = fgetcsv($handle)) !== false) {
            if (! $header || count($row) < 2) {
                continue;
            }
            $row = array_combine($header, array_pad($row, count($header), null));
            $payload = [
                'name' => $row['name'] ?? $row['ten'] ?? '',
                'phone' => $row['phone'] ?? $row['sdt'] ?? '',
                'email' => $row['email'] ?? '',
                'source_code' => $row['source_code'] ?? $row['nguon'] ?? 'other',
                'note' => $row['note'] ?? $row['ghi_chu'] ?? null,
            ];
            if (empty($payload['phone']) && empty($payload['email'])) {
                continue;
            }
            $result = $this->leads->ingest($payload, 'crm', $user);
            $result['created'] ? $created++ : $updated++;
        }
        fclose($handle);

        return redirect()->route('leads.index')->with('status', "Import xong: {$created} lead mới, {$updated} lead đã có (ghi lần chạm).");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => [$creating ? 'required' : 'nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'sso_id' => ['nullable', 'string', 'max:64'],
            'source_id' => ['required', 'exists:lead_sources,id'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'interested_product' => ['nullable', 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:2000'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
        ]);

        $source = LeadSource::query()->find($data['source_id']);
        $data['source_code'] = $source?->code;

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'sources' => LeadSource::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'sales' => request()->user()->isAdmin()
                ? User::query()->where('role', User::ROLE_SALE)->where('is_active', true)->orderBy('name')->get()
                : collect(),
        ];
    }
}
