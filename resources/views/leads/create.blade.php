<x-app-layout title="Thêm Lead">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Quản lý Leads <span>›</span>
            <a href="{{ route('leads.index') }}" class="hover:underline">Danh sách Lead</a>
            <span>›</span>
            <strong>Thêm Lead</strong>
        </div>
    </div>

    <div class="ts-panel p-6 max-w-4xl">
        <form method="POST" action="{{ route('leads.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            @include('leads._form')
            <div class="md:col-span-2 border-t border-slate-100 pt-4">
                <p class="ts-label">UTM (tuỳ chọn)</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <input class="ts-input" name="utm_source" placeholder="utm_source" value="{{ old('utm_source') }}">
                    <input class="ts-input" name="utm_medium" placeholder="utm_medium" value="{{ old('utm_medium') }}">
                    <input class="ts-input" name="utm_campaign" placeholder="utm_campaign" value="{{ old('utm_campaign') }}">
                </div>
            </div>
            <div class="md:col-span-2 flex justify-end gap-3">
                <a href="{{ route('leads.index') }}" class="ts-btn ts-btn-ghost">Hủy</a>
                <button class="ts-btn ts-btn-primary" type="submit">Lưu lead</button>
            </div>
        </form>
    </div>
</x-app-layout>
