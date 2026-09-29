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
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Lead::query()
            ->with(['source', 'stage', 'owner', 'lastTouch'])
            ->visibleTo($user)
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($q = trim((string) $request->input('q', ''))) {
            $digits = preg_replace('/\D+/', '', $q) ?: '';
            $query->where(function ($sub) use ($q, $digits) {
                $sub->where('name', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%')
                    ->orWhere('phone_normalized', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('code', 'like', '%'.$q.'%');

                if ($digits !== '') {
                    $sub->orWhere('phone', 'like', '%'.$digits.'%')
                        ->orWhere('phone_normalized', 'like', '%'.$digits.'%');
                    $tail = strlen($digits) >= 9 ? substr($digits, -9) : $digits;
                    if ($tail !== $digits) {
                        $sub->orWhere('phone_normalized', 'like', '%'.$tail.'%');
                    }
                }
            });
        }

        $tab = (string) $request->input('tab', '');
        if (in_array($tab, ['new', 'callback', 'recare'], true)) {
            $query->tab($tab);
        }

        if ($request->boolean('callback')) {
            $query->tab('callback');
        }

        $level = strtoupper((string) $request->input('level', ''));
        if (array_key_exists($level, LeadStage::levelTabs())) {
            $query->level($level);
        } else {
            $level = '';
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

        if ($request->filled('last_called_from')) {
            $query->whereDate('last_called_at', '>=', $request->input('last_called_from'));
        }

        if ($request->filled('last_called_to')) {
            $query->whereDate('last_called_at', '<=', $request->input('last_called_to'));
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
        $levelCounts = collect(LeadStage::levelTabs())
            ->mapWithKeys(fn (string $label, string $key) => [
                $key => (clone $funnelQuery)->level($key)->count(),
            ]);

        $stages = LeadStage::query()
            ->whereNotNull('level_group')
            ->orderBy('sort_order')
            ->get();

        return view('leads.index', [
            'leads' => $leads,
            'stages' => $stages,
            'levelTabs' => LeadStage::levelTabs(),
            'levelCounts' => $levelCounts,
            'currentLevel' => $level,
            'sources' => LeadSource::query()->orderBy('sort_order')->get(),
            'sales' => $user->isAdmin()
                ? User::query()->where('role', User::ROLE_SALE)->where('is_active', true)->orderBy('name')->get()
                : collect(),
            'filters' => $request->only([
                'q', 'stage_id', 'source_id', 'owner_id', 'utm_campaign',
                'tab', 'level', 'per_page', 'last_called_from', 'last_called_to', 'callback',
            ]),
            'unassignedCount' => $user->isAdmin()
                ? Lead::query()->whereNull('owner_id')->count()
                : 0,
            'callResults' => Lead::CALL_RESULTS,
        ]);
    }

    public function bulkAssign(Request $request): RedirectResponse
    {
        $this->authorize('assignAny', Lead::class);

        $data = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1'],
            'lead_ids.*' => ['integer', 'exists:leads,id'],
            'owner_id' => ['nullable', 'exists:users,id'],
        ]);

        $ownerId = $data['owner_id'] ? (int) $data['owner_id'] : null;
        $count = 0;
        foreach (Lead::query()->whereIn('id', $data['lead_ids'])->get() as $lead) {
            $this->leads->assign($lead, $ownerId, $request->user());
            $count++;
        }

        $message = $ownerId
            ? "Đã chia {$count} lead cho tư vấn viên."
            : "Đã bỏ phân công {$count} lead.";

        return back()->with('status', $message);
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
            'orders.items',
        ]);

        return view('leads.show', [
            'lead' => $lead,
            'stages' => LeadStage::query()->whereNotNull('level_group')->orderBy('sort_order')->get(),
            'levelTabs' => LeadStage::levelTabs(),
            'stageDetails' => LeadStage::detailsByGroup(),
            'sales' => request()->user()->isAdmin()
                ? User::query()->where('role', User::ROLE_SALE)->where('is_active', true)->orderBy('name')->get()
                : collect(),
            'contactTypes' => LeadActivity::CONTACT_TYPES,
            'customerTypes' => Lead::CUSTOMER_TYPES,
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
            'level_group' => ['required', 'in:'.implode(',', array_keys(LeadStage::levelTabs()))],
            'stage_id' => ['nullable', 'exists:lead_stages,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $stageId = $data['stage_id'] ?? null;
        $details = LeadStage::detailsByGroup()[$data['level_group']] ?? [];
        if ($details && ! $stageId) {
            return back()->withErrors(['stage_id' => 'Hãy chọn mức chi tiết của level.']);
        }
        if ($stageId) {
            $stage = LeadStage::query()->findOrFail((int) $stageId);
            if ($stage->level_group !== $data['level_group']) {
                return back()->withErrors(['stage_id' => 'Mức chi tiết không khớp level đã chọn.']);
            }
        } else {
            $mainSlug = strtolower($data['level_group']);
            $stage = LeadStage::query()->where('slug', $mainSlug)->firstOrFail();
        }

        $this->leads->changeStage($lead, (int) $stage->id, $request->user(), $data['reason'] ?? null);

        return back()->with('status', 'Đã cập nhật level lead.');
    }

    public function classify(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $data = $request->validate([
            'customer_type' => ['nullable', 'in:'.implode(',', array_keys(Lead::CUSTOMER_TYPES))],
        ]);

        $lead->customer_type = $data['customer_type'] ?: null;
        $lead->save();

        return back()->with('status', 'Đã cập nhật phân loại lead.');
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
            'type' => ['required', 'in:'.implode(',', array_keys(LeadActivity::CONTACT_TYPES))],
            'call_result' => ['required', 'in:'.implode(',', array_keys(Lead::CALL_RESULTS))],
            'content' => ['required', 'string', 'max:5000'],
            'callback_at' => ['nullable', 'date'],
        ]);

        $lead->call_result = $data['call_result'];
        if ($data['type'] === 'call') {
            $lead->last_called_at = now();
        }
        if (! empty($data['callback_at'])) {
            $lead->callback_at = $data['callback_at'];
            $lead->call_result = 'callback';
        } elseif ($data['call_result'] !== 'callback') {
            $lead->callback_at = null;
        }

        $content = $data['content'];
        $content = 'Trạng thái: '.(Lead::CALL_RESULTS[$lead->call_result] ?? $lead->call_result).'. '.$content;
        if ($lead->callback_at) {
            $content .= ' — hẹn liên hệ lại '.$lead->callback_at->format('d/m/Y H:i');
        }
        $lead->save();

        LeadActivity::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'content' => $content,
        ]);

        return back()->with('status', 'Đã ghi nội dung liên hệ.');
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
