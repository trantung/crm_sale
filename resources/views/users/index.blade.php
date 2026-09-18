<x-app-layout title="Users">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 Quản trị <span>›</span> <strong>Users</strong>
        </div>
        <div class="ts-actions">
            <a href="{{ route('users.create') }}" class="ts-btn ts-btn-primary">+ Tạo user</a>
        </div>
    </div>

    <p class="text-sm text-slate-500 mb-3">Mật khẩu mặc định khi tạo mới / reset: <code class="bg-white border border-slate-200 px-1 rounded">{{ $defaultPassword }}</code></p>

    <div class="ts-panel overflow-x-auto">
        <table class="ts-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <div class="font-semibold">{{ $user->name }}</div>
                            <div class="text-slate-500 text-xs">{{ $user->username }} @if($user->email) · {{ $user->email }} @endif</div>
                        </td>
                        <td>{{ $user->roleLabel() }}</td>
                        <td>
                            <span class="ts-status">
                                <i class="ts-dot {{ $user->is_active ? 'ts-dot-ok' : 'ts-dot-mute' }}"></i>
                                {{ $user->is_active ? 'Active' : 'Tắt' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap space-x-2">
                            <a href="{{ route('users.edit', $user) }}" class="ts-btn ts-btn-ghost" style="padding:6px 10px;">Sửa</a>
                            <form method="POST" action="{{ route('users.reset-password', $user) }}" class="inline" onsubmit="return confirm('Reset mật khẩu về mặc định?')">
                                @csrf
                                <button type="submit" class="ts-btn ts-btn-ghost" style="padding:6px 10px;">Reset MK</button>
                            </form>
                            @if (Auth::id() !== $user->id)
                                <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" onsubmit="return confirm('Xóa user này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ts-btn ts-btn-danger" style="padding:6px 10px;">Xóa</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="ts-pager">
            {{ $users->links('vendor.pagination.telesale') }}
            <div>Tổng cộng: <strong>{{ $users->total() }} users</strong></div>
        </div>
    </div>
</x-app-layout>
