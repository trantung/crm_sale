@php
    $level = $currentLevel ?? '';
    $tabTitle = $level && isset($levelTabs[$level])
        ? $levelTabs[$level]
        : 'Danh sách Lead';
    $keep = fn (array $extra = []) => array_filter(array_merge([
        'q' => $filters['q'] ?? null,
        'stage_id' => $filters['stage_id'] ?? null,
        'source_id' => $filters['source_id'] ?? null,
        'owner_id' => $filters['owner_id'] ?? null,
        'utm_campaign' => $filters['utm_campaign'] ?? null,
        'last_called_from' => $filters['last_called_from'] ?? null,
        'last_called_to' => $filters['last_called_to'] ?? null,
        'per_page' => $filters['per_page'] ?? null,
        'callback' => $filters['callback'] ?? null,
        'level' => $level ?: null,
    ], $extra), fn ($v) => $v !== null && $v !== '');
    $levelQuery = fn (?string $value) => route('leads.index', $keep(['level' => $value, 'callback' => null]));
    $hasAdvanced = ($filters['stage_id'] ?? '') || ($filters['source_id'] ?? '') || ($filters['owner_id'] ?? '')
        || ($filters['utm_campaign'] ?? '') || ($filters['last_called_from'] ?? '') || ($filters['last_called_to'] ?? '');
    $colspan = Auth::user()->isAdmin() ? 8 : 7;
@endphp

<x-app-layout title="{{ $tabTitle }} · CRM Telesale">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Quản lý Leads <span>›</span> <strong>{{ $tabTitle }}</strong>
        </div>
        <div class="ts-actions">
            <a href="{{ route('leads.create') }}" class="ts-btn ts-btn-primary">+ Thêm Lead</a>
            @can('import', App\Models\Lead::class)
                <a href="{{ route('leads.import') }}" class="ts-btn ts-btn-ghost">📥 Import</a>
            @endcan
        </div>
    </div>

    <div x-data="{
        filtersOpen: {{ $hasAdvanced ? 'true' : 'false' }},
        selected: [],
        ownerId: '',
        all: false,
        toggleAll(checked) {
            this.selected = checked ? Array.from(document.querySelectorAll('.lead-check')).map(c => c.value) : [];
            document.querySelectorAll('.lead-check').forEach(c => c.checked = checked);
            this.all = checked;
        },
        toggleOne() {
            this.selected = Array.from(document.querySelectorAll('.lead-check:checked')).map(c => c.value);
            this.all = this.selected.length > 0 && this.selected.length === document.querySelectorAll('.lead-check').length;
        }
    }">
        <div class="ts-tabs">
            @foreach ($levelTabs as $key => $label)
                <a href="{{ $levelQuery($key) }}" title="{{ $label }}"
                    class="ts-tab {{ $level === $key ? 'active' : '' }}">{{ $key }} ({{ $levelCounts[$key] ?? 0 }})</a>
            @endforeach
            <a href="{{ $levelQuery(null) }}" class="ts-tab {{ $level === '' ? 'active' : '' }}">Tất cả</a>
            <button type="button" class="ts-tab ml-auto" @click="filtersOpen = !filtersOpen">BỘ LỌC NÂNG CAO 🔽</button>
        </div>

        <form method="GET" class="mb-3" x-show="filtersOpen" x-cloak>
            @if ($level)
                <input type="hidden" name="level" value="{{ $level }}">
            @endif
            <div class="ts-filter-grid">
                <div>
                    <label class="ts-label">Tìm kiếm</label>
                    <input class="ts-input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tên, SĐT, mã lead...">
                </div>
                <div>
                    <label class="ts-label">Level / trạng thái</label>
                    <select name="stage_id" class="ts-select">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($stages->groupBy('level_group') as $group => $groupStages)
                            <optgroup label="{{ $levelTabs[$group] ?? $group }}">
                                @foreach ($groupStages as $stage)
                                    <option value="{{ $stage->id }}" @selected(($filters['stage_id'] ?? '') == $stage->id)>{{ $stage->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ts-label">Nguồn</label>
                    <select name="source_id" class="ts-select">
                        <option value="">Tất cả nguồn</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->id }}" @selected(($filters['source_id'] ?? '') == $source->id)>{{ $source->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ts-label">Lần cuối gọi từ</label>
                    <input class="ts-input" type="date" name="last_called_from" value="{{ $filters['last_called_from'] ?? '' }}">
                </div>
                <div>
                    <label class="ts-label">Lần cuối gọi đến</label>
                    <input class="ts-input" type="date" name="last_called_to" value="{{ $filters['last_called_to'] ?? '' }}">
                </div>
                @if (Auth::user()->isAdmin())
                    <div>
                        <label class="ts-label">Tư vấn viên</label>
                        <select name="owner_id" class="ts-select">
                            <option value="">Tất cả tư vấn viên</option>
                            <option value="unassigned" @selected(($filters['owner_id'] ?? '') === 'unassigned')">Chưa phân</option>
                            @foreach ($sales as $sale)
                                <option value="{{ $sale->id }}" @selected(($filters['owner_id'] ?? '') == $sale->id)>{{ $sale->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="ts-label">UTM campaign</label>
                    <input class="ts-input" type="text" name="utm_campaign" value="{{ $filters['utm_campaign'] ?? '' }}" placeholder="UTM campaign">
                </div>
                <div class="flex items-end">
                    <button class="ts-btn ts-btn-primary w-full justify-center" type="submit">Lọc</button>
                </div>
            </div>
        </form>

        @can('assignAny', App\Models\Lead::class)
            <form method="POST" action="{{ route('leads.bulk-assign') }}" class="ts-bulk" x-show="selected.length" x-cloak>
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="lead_ids[]" :value="id">
                </template>
                <div class="font-semibold text-[#0f2744]">Đã chọn <span x-text="selected.length"></span> lead</div>
                <select name="owner_id" class="ts-select" style="max-width:260px;" x-model="ownerId" required>
                    <option value="">— Chọn tư vấn viên —</option>
                    @foreach ($sales as $sale)
                        <option value="{{ $sale->id }}">{{ $sale->name }} ({{ $sale->username }})</option>
                    @endforeach
                </select>
                <button class="ts-btn ts-btn-primary" type="submit">Chia lead</button>
            </form>
        @endcan

        <div class="ts-panel overflow-x-auto">
            <table class="ts-table ts-table-wide">
                <thead>
                    <tr>
                        <th>
                            @if (Auth::user()->isAdmin())
                                <input type="checkbox" @change="toggleAll($el.checked)">
                            @endif
                        </th>
                        <th>Mã Lead</th>
                        <th>Khách hàng</th>
                        <th>Số điện thoại</th>
                        <th>Nguồn</th>
                        <th>Trạng thái</th>
                        <th>Lần cuối gọi</th>
                        @if (Auth::user()->isAdmin())
                            <th>Tư vấn viên</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leads as $lead)
                        <tr>
                            <td>
                                @if (Auth::user()->isAdmin())
                                    <input type="checkbox" class="lead-check" value="{{ $lead->id }}" @change="toggleOne()">
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('leads.show', $lead) }}" class="ts-code">{{ $lead->code ?: 'LD-'.$lead->id }}</a>
                            </td>
                            <td>
                                <a href="{{ route('leads.show', $lead) }}" class="font-semibold text-[#0f2744] hover:underline">{{ $lead->name }}</a>
                                <div class="text-xs text-slate-400">{{ $lead->stage?->name }}</div>
                            </td>
                            <td class="whitespace-nowrap">{{ $lead->formattedPhone() }}</td>
                            <td>{{ $lead->source?->name ?? '—' }}</td>
                            <td>
                                <span class="ts-status">
                                    <i class="ts-dot ts-dot-{{ $lead->callStatusTone() }}"></i>
                                    {{ $lead->callStatusLabel() }}
                                </span>
                            </td>
                            <td class="text-slate-500 whitespace-nowrap">{{ $lead->lastCallLabel() }}</td>
                            @if (Auth::user()->isAdmin())
                                <td>{{ $lead->owner?->name ?? 'Chưa phân' }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $colspan }}" class="text-center text-slate-500 py-10">Chưa có lead trong danh sách này.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="ts-pager">
                <div>Trang <strong class="text-[#0f2744]">{{ $leads->currentPage() }}</strong> / {{ $leads->lastPage() }}</div>
                {{ $leads->links('vendor.pagination.telesale') }}
                <form method="GET" class="flex items-center gap-2">
                    @foreach (collect($filters)->except('per_page', 'page') as $key => $value)
                        @if ($value !== null && $value !== '')
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <span>Hiển thị:</span>
                    <select name="per_page" class="ts-select" style="width:auto;padding:6px 10px;" onchange="this.form.submit()">
                        @foreach ([20, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 20) === $size)>{{ $size }} / trang</option>
                        @endforeach
                    </select>
                </form>
                <div>Tổng cộng: <strong class="text-[#0f2744]">{{ number_format($leads->total()) }} Leads</strong></div>
            </div>
        </div>
    </div>
</x-app-layout>
