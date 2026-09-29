<x-app-layout title="Sửa {{ $lead->code }}">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Quản lý Leads <span>›</span>
            <a href="{{ route('leads.index') }}" class="hover:underline">Danh sách Lead</a>
            <span>›</span>
            <a href="{{ route('leads.show', $lead) }}" class="hover:underline">{{ $lead->code }}</a>
            <span>›</span>
            <strong>Sửa</strong>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 ts-panel p-6">
            <form method="POST" action="{{ route('leads.update', $lead) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                @method('PUT')
                @include('leads._form', ['lead' => $lead])
                <div class="md:col-span-2 flex justify-end gap-3">
                    <a href="{{ route('leads.show', $lead) }}" class="ts-btn ts-btn-ghost">Hủy</a>
                    <button class="ts-btn ts-btn-primary" type="submit">Cập nhật</button>
                </div>
            </form>
        </div>
        <div class="ts-panel p-5 h-fit">
            <div class="ts-card-title">Thông tin gọi</div>
            <div class="space-y-3 text-sm">
                <div>
                    <div class="ts-label">Mã lead</div>
                    <div class="ts-code">{{ $lead->code }}</div>
                </div>
                <div>
                    <div class="ts-label">Trạng thái</div>
                    <span class="ts-status">
                        <i class="ts-dot ts-dot-{{ $lead->callStatusTone() }}"></i>
                        {{ $lead->callStatusLabel() }}
                    </span>
                </div>
                <div>
                    <div class="ts-label">Lần cuối gọi</div>
                    <div class="font-semibold">{{ $lead->lastCallLabel() }}</div>
                </div>
                <div>
                    <div class="ts-label">Số điện thoại</div>
                    <div class="font-semibold">{{ $lead->formattedPhone() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
