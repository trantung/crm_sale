@php
    $tab = $filters['tab'] ?? '';
    $tabTitle = match ($tab) {
        'new' => 'Lead mới',
        'callback' => 'Hẹn gọi lại',
        'recare' => 'Chăm sóc lại',
        default => 'Danh sách Lead cần gọi',
    };
    $tabQuery = fn (?string $value) => route('leads.index', array_filter([
        'q' => $filters['q'] ?? null,
        'stage_id' => $filters['stage_id'] ?? null,
        'source_id' => $filters['source_id'] ?? null,
        'owner_id' => $filters['owner_id'] ?? null,
        'utm_campaign' => $filters['utm_campaign'] ?? null,
        'per_page' => $filters['per_page'] ?? null,
        'tab' => $value,
    ], fn ($v) => $v !== null && $v !== ''));
@endphp

<x-app-layout title="{{ $tabTitle }} · CRM Telesale">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Quản lý Leads <span>›</span> <strong>{{ $tabTitle }}</strong>
        </div>
        <div class="ts-actions">
            <a href="{{ route('leads.create') }}" class="ts-btn ts-btn-primary">+ Thêm Lead</a>
            <a href="{{ route('leads.import') }}" class="ts-btn ts-btn-ghost">📥 Import</a>
        </div>
    </div>

    <div x-data="{ filtersOpen: {{ ($filters['stage_id'] ?? $filters['source_id'] ?? $filters['owner_id'] ?? $filters['utm_campaign'] ?? '') ? 'true' : 'false' }} }">
        <div class="ts-tabs">
            <a href="{{ $tabQuery('new') }}" class="ts-tab {{ $tab === 'new' ? 'active' : '' }}">🔥 Lead mới ({{ $tabCounts['new'] }})</a>
            <a href="{{ $tabQuery('callback') }}" class="ts-tab {{ $tab === 'callback' ? 'active' : '' }}">⏰ Hẹn gọi lại ({{ $tabCounts['callback'] }})</a>
            <a href="{{ $tabQuery('recare') }}" class="ts-tab {{ $tab === 'recare' ? 'active' : '' }}">❌ Chăm sóc lại ({{ $tabCounts['recare'] }})</a>
            <a href="{{ $tabQuery(null) }}" class="ts-tab {{ $tab === '' ? 'active' : '' }}">Tất cả</a>
            <button type="button" class="ts-tab ml-auto" @click="filtersOpen = !filtersOpen">BỘ LỌC NÂNG CAO 🔽</button>
        </div>

        <form method="GET" class="mb-3" x-show="filtersOpen" x-cloak>
            @if ($tab)
                <input type="hidden" name="tab" value="{{ $tab }}">
            @endif
            <div class="grid grid-cols-1 md:grid-cols-6 gap-3 bg-white border border-slate-200 rounded-xl p-4">
                <input class="ts-input md:col-span-2" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tên, SĐT, mã lead...">
                <select name="stage_id" class="ts-select">
                    <option value="">Tất cả stage</option>
                    @foreach ($stages as $stage)
                        <option value="{{ $stage->id }}" @selected(($filters['stage_id'] ?? '') == $stage->id)>{{ $stage->name }}</option>
                    @endforeach
                </select>
                <select name="source_id" class="ts-select">
                    <option value="">Tất cả nguồn</option>
                    @foreach ($sources as $source)
                        <option value="{{ $source->id }}" @selected(($filters['source_id'] ?? '') == $source->id)>{{ $source->name }}</option>
                    @endforeach
                </select>
                @if (Auth::user()->isAdmin())
                    <select name="owner_id" class="ts-select">
                        <option value="">Tất cả sale</option>
                        <option value="unassigned" @selected(($filters['owner_id'] ?? '') === 'unassigned')">Chưa phân</option>
                        @foreach ($sales as $sale)
                            <option value="{{ $sale->id }}" @selected(($filters['owner_id'] ?? '') == $sale->id)>{{ $sale->name }}</option>
                        @endforeach
                    </select>
                @endif
                <div class="flex gap-2">
                    <input class="ts-input" type="text" name="utm_campaign" value="{{ $filters['utm_campaign'] ?? '' }}" placeholder="UTM campaign">
                    <button class="ts-btn ts-btn-primary" type="submit">Lọc</button>
                </div>
            </div>
        </form>
    </div>

    <div class="ts-panel overflow-x-auto" x-data="{
        all: false,
        callOpen: false,
        callAction: '',
        callName: '',
        callPhone: '',
        callResult: 'no_answer',
        openCall(action, name, phone) {
            this.callAction = action;
            this.callName = name;
            this.callPhone = phone;
            this.callOpen = true;
        }
    }">
        <table class="ts-table ts-table-wide">
            <thead>
                <tr>
                    <th><input type="checkbox" @change="all = $el.checked; document.querySelectorAll('.lead-check').forEach(c => c.checked = all)"></th>
                    <th>Mã Lead</th>
                    <th>Khách hàng</th>
                    <th>Số điện thoại</th>
                    <th>Nguồn</th>
                    <th>Trạng thái</th>
                    <th>Lần cuối gọi</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leads as $lead)
                    <tr>
                        <td><input type="checkbox" class="lead-check" value="{{ $lead->id }}"></td>
                        <td>
                            <a href="{{ route('leads.show', $lead) }}" class="ts-code">{{ $lead->code ?: 'LD-'.$lead->id }}</a>
                        </td>
                        <td>
                            <a href="{{ route('leads.show', $lead) }}" class="font-semibold text-[#0f2744] hover:underline">{{ $lead->name }}</a>
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
                        <td>
                            <a href="{{ $lead->telHref() ?: '#' }}" class="ts-btn ts-btn-call"
                                @click="openCall('{{ route('leads.call', $lead) }}', @js($lead->name), @js($lead->formattedPhone()))">
                                📞 GỌI
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-slate-500 py-10">Chưa có lead trong danh sách này.</td>
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

        @include('leads._call-modal')
    </div>
</x-app-layout>
