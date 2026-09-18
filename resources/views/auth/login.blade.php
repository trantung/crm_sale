<div class="mb-6">
    <div class="ts-brand" style="color:#0f2744;letter-spacing:0.08em;">
        <span>CRM</span> TELESALE
    </div>
    <p class="mt-3 text-sm text-slate-500">Đăng nhập bằng username và mật khẩu</p>
</div>

<x-auth-session-status class="mb-4" :status="session('status')" />

<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf

    <div>
        <label class="ts-label" for="username">Username</label>
        <input id="username" class="ts-input" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
        <x-input-error :messages="$errors->get('username')" class="mt-2" />
    </div>

    <div>
        <label class="ts-label" for="password">Mật khẩu</label>
        <input id="password" class="ts-input" type="password" name="password" required autocomplete="current-password">
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>

    <label for="remember_me" class="inline-flex items-center gap-2">
        <input id="remember_me" type="checkbox" class="rounded border-slate-300" name="remember">
        <span class="text-sm text-slate-600">Ghi nhớ đăng nhập</span>
    </label>

    <button type="submit" class="ts-btn ts-btn-primary w-full justify-center">Đăng nhập</button>
</form>
