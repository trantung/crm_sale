<x-app-layout title="Sản phẩm / Bảng giá">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Catalog <span>›</span> <strong>Sản phẩm / Bảng giá</strong>
        </div>
        <div class="ts-actions">
            <a href="{{ route('products.create') }}" class="ts-btn ts-btn-primary">+ Thêm khóa học</a>
        </div>
    </div>

    <div class="ts-panel overflow-x-auto">
        <table class="ts-table">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Khóa học</th>
                    <th>Giá</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td class="ts-code">{{ $product->code }}</td>
                        <td>
                            <div class="font-semibold">{{ $product->name }}</div>
                            @if ($product->description)
                                <div class="text-xs text-slate-500">{{ $product->description }}</div>
                            @endif
                        </td>
                        <td class="font-semibold">{{ $product->formattedPrice() }}</td>
                        <td>
                            <span class="ts-status">
                                <i class="ts-dot {{ $product->is_active ? 'ts-dot-ok' : 'ts-dot-mute' }}"></i>
                                {{ $product->is_active ? 'Đang bán' : 'Ẩn' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap space-x-2">
                            <a href="{{ route('products.edit', $product) }}" class="ts-btn ts-btn-ghost" style="padding:6px 10px;">Sửa</a>
                            @can('delete', $product)
                                <form method="POST" action="{{ route('products.destroy', $product) }}" class="inline" onsubmit="return confirm('Xóa khóa học này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ts-btn ts-btn-danger" style="padding:6px 10px;">Xóa</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-slate-500 py-10">Chưa có khóa học. Hãy thêm bảng giá để tạo đơn hàng.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="ts-pager">
            {{ $products->links('vendor.pagination.telesale') }}
            <div>Tổng cộng: <strong>{{ $products->total() }} khóa học</strong></div>
        </div>
    </div>
</x-app-layout>
