<x-app-layout title="{{ $lead->code }} · {{ $lead->name }}">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Quản lý Leads <span>›</span>
            <a href="{{ route('leads.index') }}" class="hover:underline">Danh sách Lead</a>
            <span>›</span>
            <strong>{{ $lead->code ?: 'LD-'.$lead->id }}</strong>
        </div>
        <div class="ts-actions">
            <a href="{{ route('orders.create', ['lead_id' => $lead->id]) }}" class="ts-btn ts-btn-primary">+ Tạo đơn</a>
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
                        <div class="ts-label">Level</div>
                        <div class="font-semibold">{{ $lead->stage?->name }}</div>
                        @php $latestReason = $lead->stageHistories->firstWhere('reason'); @endphp
                        @if ($latestReason?->reason)
                            <div class="text-xs text-slate-500 mt-1">Lý do: {{ $latestReason->reason }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="ts-label">Tư vấn viên</div>
                        <div class="font-semibold">{{ $lead->owner?->name ?? 'Chưa phân' }}</div>
                    </div>
                    <div>
                        <div class="ts-label">Lần cuối gọi</div>
                        <div class="font-semibold">{{ $lead->lastCallLabel() }}</div>
                    </div>
                    <div>
                        <div class="ts-label">Phân loại</div>
                        <div class="font-semibold">{{ $lead->customerTypeLabel() }}</div>
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

            @if ($lead->orders->isNotEmpty())
                <div class="ts-panel p-5">
                    <div class="ts-card-title">Đơn hàng nội bộ</div>
                    <table class="ts-table">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Sản phẩm</th>
                                <th>Tổng tiền</th>
                                <th>Ngày</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lead->orders as $order)
                                <tr>
                                    <td><a class="ts-code" href="{{ route('orders.show', $order) }}">{{ $order->code }}</a></td>
                                    <td>{{ $order->productNames() }}</td>
                                    <td class="font-semibold">{{ $order->formattedTotal() }}</td>
                                    <td>{{ $order->created_at?->format('d/m/Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="ts-panel p-5">
                <div class="ts-card-title">Ghi liên hệ</div>
                <form method="POST" action="{{ route('leads.activities.store', $lead) }}" class="grid grid-cols-1 gap-3 mb-5">
                    @csrf
                    <div>
                        <label class="ts-label">Hình thức</label>
                        <select name="type" class="ts-select" required>
                            @foreach ($contactTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', 'call') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ts-label">Nội dung trao đổi</label>
                        <textarea name="content" rows="3" class="ts-textarea" required placeholder="Nội dung trao đổi với khách...">{{ old('content') }}</textarea>
                    </div>
                    <div>
                        <label class="ts-label">Hẹn liên hệ lại</label>
                        <input type="datetime-local" name="callback_at" class="ts-input" value="{{ old('callback_at', $lead->callback_at?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="flex justify-end">
                        <button class="ts-btn ts-btn-primary" type="submit">Lưu liên hệ</button>
                    </div>
                </form>
                <div class="ts-label mb-2">Lịch sử liên hệ</div>
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
            <div class="ts-panel p-5" x-data="{
                level: '{{ $lead->stage?->level_group ?: 'L0' }}',
                detailId: '{{ $lead->stage && ! $lead->stage->isMain() ? $lead->stage_id : '' }}',
                details: {{ \Illuminate\Support\Js::from($stageDetails) }}
            }">
                <div class="ts-card-title">Chuyển level</div>
                <form method="POST" action="{{ route('leads.stage', $lead) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="ts-label">Level chính</label>
                        <select name="level_group" class="ts-select" x-model="level" @change="detailId = ''">
                            @foreach ($levelTabs as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ts-label">Chi tiết level</label>
                        <select name="stage_id" class="ts-select" x-model="detailId">
                            <option value="">— Chọn mức chi tiết —</option>
                            @foreach ($stageDetails as $group => $items)
                                @foreach ($items as $item)
                                    <option value="{{ $item['id'] }}" x-show="level === '{{ $group }}'" x-cloak>{{ $item['name'] }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-400 mt-1" x-show="!(details[level] || []).length">Level này không có mức chi tiết (L0).</p>
                    </div>
                    <div>
                        <label class="ts-label" for="stage-reason">Lý do (tuỳ chọn)</label>
                        <textarea id="stage-reason" name="reason" rows="3" class="ts-textarea" placeholder="Ví dụ: khách hẹn chuyển khoản, sai đối tượng...">{{ old('reason') }}</textarea>
                    </div>
                    <button class="ts-btn ts-btn-primary" type="submit">Cập nhật level</button>
                </form>
            </div>

            <div class="ts-panel p-5">
                <div class="ts-card-title">Phân loại lead</div>
                <form method="POST" action="{{ route('leads.classify', $lead) }}" class="space-y-3">
                    @csrf
                    <select name="customer_type" class="ts-select">
                        <option value="">— Chọn phân loại —</option>
                        @foreach ($customerTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('customer_type', $lead->customer_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="ts-btn ts-btn-primary" type="submit">Lưu phân loại</button>
                </form>
            </div>

            @can('assign', $lead)
                <div class="ts-panel p-5">
                    <div class="ts-card-title">Phân tư vấn viên</div>
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
                <div class="ts-card-title">Lịch sử level</div>
                <ul class="space-y-2 text-sm">
                    @foreach ($lead->stageHistories as $history)
                        <li>
                            {{ $history->fromStage?->name ?? '—' }} → <span class="font-semibold">{{ $history->toStage?->name }}</span>
                            <div class="text-xs text-slate-400">{{ $history->changer?->name ?? 'Hệ thống' }} · {{ $history->created_at?->format('d/m/Y H:i') }}</div>
                            @if ($history->reason)
                                <div class="text-slate-700 mt-1">Lý do: {{ $history->reason }}</div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
