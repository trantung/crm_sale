<x-guest-layout>
    <div class="ts-login-head">
        <div class="ts-login-mark">CRM</div>
        <h1>Đăng nhập</h1>
        <p>CRM Telesale · IELTS Checkmate</p>
    </div>

    <x-auth-session-status class="ts-login-status" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="ts-login-form">
        @csrf

        <div>
            <label class="ts-label" for="username">Username</label>
            <input id="username" class="ts-input" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="Nhập username">
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <div>
            <label class="ts-label" for="password">Mật khẩu</label>
            <input id="password" class="ts-input" type="password" name="password" required autocomplete="current-password" placeholder="Nhập mật khẩu">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="ts-login-remember">
            <input id="remember_me" type="checkbox" name="remember">
            <span>Ghi nhớ đăng nhập</span>
        </label>

        <button type="submit" class="ts-btn ts-btn-primary ts-login-submit">Đăng nhập</button>
    </form>
</x-guest-layout>
