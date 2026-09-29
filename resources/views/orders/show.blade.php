<x-app-layout title="{{ $order->code }}">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Doanh thu <span>›</span>
            <a href="{{ route('orders.index') }}" class="hover:underline">Đơn hàng</a>
            <span>›</span> <strong>{{ $order->code }}</strong>
        </div>
        <div class="ts-actions">
            <a href="{{ route('orders.create') }}" class="ts-btn ts-btn-ghost">+ Đơn khác</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="ts-panel p-5">
                <div class="ts-code text-sm">{{ $order->code }}</div>
                <h1 class="text-2xl font-extrabold text-[#0f2744] mt-1">{{ $order->customer_name }}</h1>
                <div class="mt-2 text-slate-600">{{ $order->customer_phone ?: '—' }}</div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5 text-sm">
                    <div>
                        <div class="ts-label">Nguồn</div>
                        <div class="font-semibold">{{ $order->source?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="ts-label">Tư vấn viên</div>
                        <div class="font-semibold">{{ $order->owner?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="ts-label">Người tạo</div>
                        <div class="font-semibold">{{ $order->creator?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="ts-label">Ngày tạo</div>
                        <div class="font-semibold">{{ $order->created_at?->format('d/m/Y H:i') }}</div>
                    </div>
                    @if ($order->lead)
                        <div class="col-span-2">
                            <div class="ts-label">Lead</div>
                            <a href="{{ route('leads.show', $order->lead) }}" class="font-semibold text-[#0f2744] hover:underline">{{ $order->lead->code }} · {{ $order->lead->name }}</a>
                        </div>
                    @endif
                    <div class="col-span-2">
                        <div class="ts-label">Ghi chú</div>
                        <div>{{ $order->note ?: '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="ts-panel overflow-x-auto">
                <table class="ts-table">
                    <thead>
                        <tr>
                            <th>Khóa học</th>
                            <th>Đơn giá</th>
                            <th>SL</th>
                            <th>CK %</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="font-semibold">{{ $item->product_name }}</td>
                                <td>{{ number_format($item->unit_price, 0, ',', '.') }} đ</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ rtrim(rtrim(number_format($item->discount_percent, 2, ',', '.'), '0'), ',') }}%</td>
                                <td class="font-semibold">{{ $item->formattedLineTotal() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ts-panel p-5 h-fit">
            <div class="ts-card-title">Tổng kết</div>
            <div class="flex justify-between text-sm py-2 border-b border-slate-100">
                <span>Tạm tính</span>
                <strong>{{ number_format($order->subtotal, 0, ',', '.') }} đ</strong>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-slate-100">
                <span>Chiết khấu</span>
                <strong>{{ number_format($order->subtotal - $order->total, 0, ',', '.') }} đ</strong>
            </div>
            <div class="flex justify-between items-center pt-3">
                <span class="font-semibold">Tổng tiền</span>
                <span class="text-2xl font-extrabold text-[#0f2744]">{{ $order->formattedTotal() }}</span>
            </div>
            <p class="text-xs text-slate-500 mt-4">Đơn nội bộ dùng cho kế toán đối soát doanh thu và tính hoa hồng TVV. Không gửi hóa đơn cho khách.</p>
        </div>
    </div>
</x-app-layout>
