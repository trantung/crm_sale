<x-app-layout title="{{ $lead->code }} · {{ $lead->name }}">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Quản lý Leads <span>›</span>
            <a href="{{ route('leads.index') }}" class="hover:underline">Danh sách Lead</a>
            <span>›</span>
            <strong>{{ $lead->code ?: 'LD-'.$lead->id }}</strong>
        </div>
        <div class="ts-actions">
            @if ($lead->telHref())
                <a href="{{ $lead->telHref() }}" class="ts-btn ts-btn-call" onclick="document.getElementById('call-box')?.scrollIntoView({behavior:'smooth'})">📞 GỌI</a>
            @endif
            <a href="{{ route('leads.edit', $lead) }}" class="ts-btn ts-btn-ghost">Sửa</a>
            @can('delete', $lead)
                <form method="POST" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('Xóa lead này?')">
                    @csrf
                    @method('DELETE')
                    <button class="ts-btn ts-btn-danger" type="submit">Xóa</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="ts-panel p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="ts-code text-sm">{{ $lead->code }}</div>
                        <h1 class="text-2xl font-extrabold text-[#0f2744] mt-1">{{ $lead->name }}</h1>
                        <div class="mt-2 text-slate-600">{{ $lead->formattedPhone() }} @if($lead->email) · {{ $lead->email }} @endif</div>
                    </div>
                    <span class="ts-status text-base">
                        <i class="ts-dot ts-dot-{{ $lead->callStatusTone() }}"></i>
                        {{ $lead->callStatusLabel() }}
                    </span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5 text-sm">
                    <div>
                        <div class="ts-label">Nguồn</div>
                        <div class="font-semibold">{{ $lead->source?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="ts-label">Stage</div>
                        <div class="font-semibold">{{ $lead->stage?->name }}</div>
                    </div>
                    <div>
                        <div class="ts-label">Sale phụ trách</div>
                        <div class="font-semibold">{{ $lead->owner?->name ?? 'Chưa phân' }}</div>
                    </div>
                    <div>
                        <div class="ts-label">Lần cuối gọi</div>
                        <div class="font-semibold">{{ $lead->lastCallLabel() }}</div>
                    </div>
                    <div>
                        <div class="ts-label">Sản phẩm quan tâm</div>
                        <div class="font-semibold">{{ $lead->interested_product ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="ts-label">Kênh vào</div>
                        <div class="font-semibold">{{ $lead->origin === 'api' ? 'API phễu' : 'CRM' }}</div>
                    </div>
                    <div class="col-span-2">
                        <div class="ts-label">Ghi chú</div>
                        <div>{{ $lead->note ?: '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="ts-panel p-5" id="call-box">
                <div class="ts-card-title">Ghi kết quả cuộc gọi</div>
                <form method="POST" action="{{ route('leads.call', $lead) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @csrf
                    <div>
                        <label class="ts-label">Kết quả</label>
                        <select name="call_result" class="ts-select" x-data="{ v: '{{ old('call_result', $lead->call_result === 'not_called' ? 'no_answer' : $lead->call_result) }}' }" x-model="v" id="detail-call-result">
                            @foreach ($callResults as $value => $label)
                                @if ($value !== 'not_called')
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ts-label">Hẹn gọi lại</label>
                        <input type="datetime-local" name="callback_at" class="ts-input" value="{{ old('callback_at', $lead->callback_at?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div>
                        <label class="ts-label">Ghi chú cuộc gọi</label>
                        <input type="text" name="content" class="ts-input" placeholder="Nội dung trao đổi...">
                    </div>
                    <div class="md:col-span-3 flex justify-end">
                        <button class="ts-btn ts-btn-call" type="submit">📞 Lưu kết quả gọi</button>
                    </div>
                </form>
            </div>

            <div class="ts-panel p-5">
                <div class="ts-card-title">UTM / lần chạm</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg border border-slate-200 p-3">
                        <div class="ts-label">First-touch</div>
                        <div>{{ $lead->firstTouch?->utmSummary() }}</div>
                        <div class="text-slate-500 mt-1 break-all">{{ $lead->firstTouch?->landing_url }}</div>
                        <div class="text-xs text-slate-400 mt-1">{{ $lead->firstTouch?->created_at?->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <div class="ts-label">Last-touch</div>
                        <div>{{ $lead->lastTouch?->utmSummary() }}</div>
                        <div class="text-slate-500 mt-1 break-all">{{ $lead->lastTouch?->landing_url }}</div>
                        <div class="text-xs text-slate-400 mt-1">{{ $lead->lastTouch?->created_at?->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
                @if ($lead->leadTouches->count())
                    <div class="mt-4 overflow-x-auto">
                        <table class="ts-table">
                            <thead>
                                <tr>
                                    <th>Lúc</th>
                                    <th>Kênh</th>
                                    <th>UTM</th>
                                    <th>Landing</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lead->leadTouches as $touch)
                                    <tr>
                                        <td>{{ $touch->created_at?->format('d/m H:i') }}</td>
                                        <td>{{ $touch->channel }}</td>
                                        <td>{{ $touch->utmSummary() }}</td>
                                        <td class="truncate max-w-xs">{{ $touch->landing_url }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="ts-panel p-5">
                <div class="ts-card-title">Hoạt động</div>
                <form method="POST" action="{{ route('leads.activities.store', $lead) }}" class="flex flex-col md:flex-row gap-2 mb-4">
                    @csrf
                    <select name="type" class="ts-select md:w-40">
                        @foreach ($activityTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="content" required class="ts-input flex-1" placeholder="Nội dung...">
                    <button class="ts-btn ts-btn-primary" type="submit">Ghi</button>
                </form>
                <ul class="space-y-3 text-sm">
                    @forelse ($lead->activities as $activity)
                        <li class="border-b border-slate-100 pb-2">
                            <span class="font-semibold">{{ $activity->typeLabel() }}</span>
                            · {{ $activity->user?->name ?? 'Hệ thống' }}
                            · <span class="text-slate-400">{{ $activity->created_at?->format('d/m/Y H:i') }}</span>
                            <div class="text-slate-700 mt-1">{{ $activity->content }}</div>
                        </li>
                    @empty
                        <li class="text-slate-500">Chưa có hoạt động.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-4">
            <div class="ts-panel p-5">
                <div class="ts-card-title">Chuyển stage</div>
                <form method="POST" action="{{ route('leads.stage', $lead) }}" class="space-y-3">
                    @csrf
                    <select name="stage_id" class="ts-select">
                        @foreach ($stages as $stage)
                            <option value="{{ $stage->id }}" @selected($lead->stage_id === $stage->id)>{{ $stage->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="reason" class="ts-input" placeholder="Lý do (tuỳ chọn)">
                    <button class="ts-btn ts-btn-primary" type="submit">Cập nhật stage</button>
                </form>
            </div>

            @can('assign', $lead)
                <div class="ts-panel p-5">
                    <div class="ts-card-title">Phân sale</div>
                    <form method="POST" action="{{ route('leads.assign', $lead) }}" class="space-y-3">
                        @csrf
                        <select name="owner_id" class="ts-select">
                            <option value="">Chưa phân</option>
                            @foreach ($sales as $sale)
                                <option value="{{ $sale->id }}" @selected($lead->owner_id === $sale->id)>{{ $sale->name }} ({{ $sale->username }})</option>
                            @endforeach
                        </select>
                        <button class="ts-btn ts-btn-primary" type="submit">Lưu phân công</button>
                    </form>
                </div>
            @endcan

            <div class="ts-panel p-5">
                <div class="ts-card-title">Lịch sử stage</div>
                <ul class="space-y-2 text-sm">
                    @foreach ($lead->stageHistories as $history)
                        <li>
                            {{ $history->fromStage?->name ?? '—' }} → <span class="font-semibold">{{ $history->toStage?->name }}</span>
                            <div class="text-xs text-slate-400">{{ $history->changer?->name ?? 'Hệ thống' }} · {{ $history->created_at?->format('d/m/Y H:i') }}</div>
                            @if ($history->reason)
                                <div class="text-slate-500">{{ $history->reason }}</div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
