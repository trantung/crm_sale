<x-app-layout title="Tạo user">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Quản trị <span>›</span>
            <a href="{{ route('users.index') }}" class="hover:underline">Users</a>
            <span>›</span>
            <strong>Tạo user</strong>
        </div>
    </div>

    <div class="ts-panel p-6 max-w-2xl">
        <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
            @csrf
            @include('users._form')
            <p class="text-sm text-slate-500">User mới nhận mật khẩu mặc định. Họ đổi sau khi đăng nhập.</p>
            <div class="flex justify-end gap-3">
                <a href="{{ route('users.index') }}" class="ts-btn ts-btn-ghost">Hủy</a>
                <button class="ts-btn ts-btn-primary" type="submit">Tạo user</button>
            </div>
        </form>
    </div>
</x-app-layout>
