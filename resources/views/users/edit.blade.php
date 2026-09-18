<x-app-layout title="Sửa user">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Quản trị <span>›</span>
            <a href="{{ route('users.index') }}" class="hover:underline">Users</a>
            <span>›</span>
            <strong>Sửa {{ $user->name }}</strong>
        </div>
    </div>

    <div class="ts-panel p-6 max-w-2xl">
        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('users._form', ['user' => $user])
            <div class="flex justify-end gap-3">
                <a href="{{ route('users.index') }}" class="ts-btn ts-btn-ghost">Hủy</a>
                <button class="ts-btn ts-btn-primary" type="submit">Cập nhật</button>
            </div>
        </form>
    </div>
</x-app-layout>
