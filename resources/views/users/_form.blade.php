@php $user = $user ?? null; @endphp

<div>
    <label class="ts-label" for="name">Tên</label>
    <input id="name" name="name" class="ts-input" value="{{ old('name', $user->name ?? '') }}" required>
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>
<div>
    <label class="ts-label" for="username">Username</label>
    <input id="username" name="username" class="ts-input" value="{{ old('username', $user->username ?? '') }}" required>
    <x-input-error :messages="$errors->get('username')" class="mt-2" />
</div>
<div>
    <label class="ts-label" for="email">Email</label>
    <input id="email" name="email" type="email" class="ts-input" value="{{ old('email', $user->email ?? '') }}">
    <x-input-error :messages="$errors->get('email')" class="mt-2" />
</div>
<div>
    <label class="ts-label" for="role">Role</label>
    <select id="role" name="role" class="ts-select" required>
        <option value="sale" @selected(old('role', $user->role ?? 'sale') === 'sale')">Sale</option>
        <option value="admin" @selected(old('role', $user->role ?? '') === 'admin')">Admin</option>
    </select>
    <x-input-error :messages="$errors->get('role')" class="mt-2" />
</div>
<div class="flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-slate-300"
        @checked(old('is_active', $user->is_active ?? true))>
    <label for="is_active" class="text-sm font-semibold text-slate-600">Đang hoạt động</label>
</div>
