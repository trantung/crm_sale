@php
    $hasAdvanced = ($filters['code'] ?? '') || ($filters['source_id'] ?? '') || ($filters['created_from'] ?? '')
        || ($filters['created_to'] ?? '') || ($filters['owner_id'] ?? '') || ($filters['total_value'] ?? '');
@endphp

<x-app-layout title="Doanh thu">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Doanh thu <span>›</span> <strong>Đơn hàng nội bộ</strong>
        </div>
        <div class="ts-actions">
            <a href="{{ route('orders.create') }}" class="ts-btn ts-btn-primary">+ Tạo đơn hàng</a>
            <a href="{{ route('products.index') }}" class="ts-btn ts-btn-ghost">Bảng giá</a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
        <div class="ts-stat">
            <div class="k">Số đơn hàng</div>
            <div class="v">{{ number_format($orderCount) }}</div>
        </div>
        <div class="ts-stat">
            <div class="k">Tổng doanh thu</div>
            <div class="v">{{ number_format($revenueTotal, 0, ',', '.') }} đ</div>
        </div>
    </div>

    <div x-data="{ filtersOpen: {{ $hasAdvanced ? 'true' : 'false' }} }">
        <div class="ts-tabs">
            <span class="ts-tab active">Đơn hàng</span>
            <button type="button" class="ts-tab ml-auto" @click="filtersOpen = !filtersOpen">BỘ LỌC NÂNG CAO 🔽</button>
        </div>

        <form method="GET" class="mb-3" x-show="filtersOpen" x-cloak>
            <div class="ts-filter-grid">
                <div>
                    <label class="ts-label">Thời gian tạo từ</label>
                    <input class="ts-input" type="date" name="created_from" value="{{ $filters['created_from'] ?? '' }}">
                </div>
                <div>
                    <label class="ts-label">Thời gian tạo đến</label>
                    <input class="ts-input" type="date" name="created_to" value="{{ $filters['created_to'] ?? '' }}">
                </div>
                <div>
                    <label class="ts-label">Mã đơn hàng</label>
                    <input class="ts-input" type="text" name="code" value="{{ $filters['code'] ?? '' }}" placeholder="DH-2026...">
                </div>
                <div>
                    <label class="ts-label">Nguồn đơn hàng</label>
                    <select name="source_id" class="ts-select">
                        <option value="">Tất cả nguồn</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->id }}" @selected(($filters['source_id'] ?? '') == $source->id)>{{ $source->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ts-label">Tổng giá trị</label>
                    <div class="flex gap-2">
                        <select name="total_op" class="ts-select" style="max-width:110px;">
                            @foreach (['>=' => '≥', '>' => '>', '=' => '=', '<' => '<', '<=' => '≤'] as $op => $label)
                                <option value="{{ $op }}" @selected(($filters['total_op'] ?? '>=') === $op)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input class="ts-input" type="number" min="0" name="total_value" value="{{ $filters['total_value'] ?? '' }}" placeholder="VD: 10000000">
                    </div>
                </div>
                @if (Auth::user()->isAdmin())
                    <div>
                        <label class="ts-label">Tư vấn viên</label>
                        <select name="owner_id" class="ts-select">
                            <option value="">Tất cả tư vấn viên</option>
                            @foreach ($sales as $sale)
                                <option value="{{ $sale->id }}" @selected(($filters['owner_id'] ?? '') == $sale->id)>{{ $sale->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="flex items-end">
                    <button class="ts-btn ts-btn-primary w-full justify-center" type="submit">Tìm kiếm</button>
                </div>
            </div>
        </form>
    </div>

    <div class="ts-panel overflow-x-auto">
        <table class="ts-table ts-table-wide">
            <thead>
                <tr>
                    <th>Mã đơn hàng</th>
                    <th>Họ tên</th>
                    <th>SĐT</th>
                    <th>Sản phẩm mua</th>
                    <th>Tổng tiền</th>
                    @if (Auth::user()->isAdmin())
                        <th>Tư vấn viên</th>
                    @endif
                    <th>Ngày tạo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('orders.show', $order) }}" class="ts-code">{{ $order->code }}</a>
                        </td>
                        <td>
                            <a href="{{ route('orders.show', $order) }}" class="font-semibold text-[#0f2744] hover:underline">{{ $order->customer_name }}</a>
                        </td>
                        <td>{{ $order->customer_phone ?: '—' }}</td>
                        <td class="whitespace-normal min-w-[180px]">{{ $order->productNames() }}</td>
                        <td class="font-semibold">{{ $order->formattedTotal() }}</td>
                        @if (Auth::user()->isAdmin())
                            <td>{{ $order->owner?->name ?? '—' }}</td>
                        @endif
                        <td class="text-slate-500">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ Auth::user()->isAdmin() ? 7 : 6 }}" class="text-center text-slate-500 py-10">Chưa có đơn hàng.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="ts-pager">
            <div>Trang <strong class="text-[#0f2744]">{{ $orders->currentPage() }}</strong> / {{ $orders->lastPage() }}</div>
            {{ $orders->links('vendor.pagination.telesale') }}
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
            <div>Tổng cộng: <strong class="text-[#0f2744]">{{ number_format($orders->total()) }} đơn</strong></div>
        </div>
    </div>
</x-app-layout>
