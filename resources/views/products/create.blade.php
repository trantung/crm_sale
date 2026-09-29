<x-app-layout title="Thêm khóa học">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Catalog <span>›</span>
            <a href="{{ route('products.index') }}" class="hover:underline">Sản phẩm</a>
            <span>›</span> <strong>Thêm khóa học</strong>
        </div>
    </div>

    <form method="POST" action="{{ route('products.store') }}" class="ts-panel p-5 grid grid-cols-1 md:grid-cols-2 gap-4 max-w-3xl">
        @csrf
        @include('products._form')
        <div class="md:col-span-2 flex justify-end gap-2">
            <a href="{{ route('products.index') }}" class="ts-btn ts-btn-ghost">Hủy</a>
            <button class="ts-btn ts-btn-primary" type="submit">Lưu khóa học</button>
        </div>
    </form>
</x-app-layout>
