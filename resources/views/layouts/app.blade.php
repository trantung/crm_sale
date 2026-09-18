<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'CRM Telesale' }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=be-vietnam-pro:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="ts-body antialiased">
        <div class="ts-shell" x-data="{ navOpen: false }">
            <header class="ts-topbar">
                <a href="{{ route('dashboard') }}" class="ts-brand">
                    <span>CRM</span> TELESALE
                </a>

                <form class="ts-search hidden sm:block" method="GET" action="{{ route('leads.index') }}">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/></svg>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tên, SĐT, mã lead...">
                </form>

                <div class="ts-top-actions">
                    <a class="ts-bell" href="{{ route('leads.index', ['tab' => 'callback']) }}" title="Hẹn gọi lại hôm nay">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/></svg>
                        @if (($headerAlertCount ?? 0) > 0)
                            <em>{{ $headerAlertCount > 9 ? '9+' : $headerAlertCount }}</em>
                        @endif
                    </a>
                    <a href="{{ route('profile.edit') }}" class="ts-user">
                        <div class="ts-avatar">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</div>
                        <div class="hidden sm:block leading-tight">
                            <div class="text-sm font-semibold">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-slate-300">({{ Auth::user()->roleLabel() }})</div>
                        </div>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="ts-btn ts-btn-ghost" style="background:#16365c;color:#fff;border-color:#16365c;padding:8px 10px;">Thoát</button>
                    </form>
                    <button type="button" class="sm:hidden ts-btn ts-btn-ghost" style="background:#16365c;color:#fff;border:0;" @click="navOpen = !navOpen">Menu</button>
                </div>
            </header>

            <div class="bg-[#16365c] text-slate-200 text-sm hidden sm:block">
                <div class="max-w-[1440px] mx-auto px-5 flex gap-5 h-10 items-center">
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'text-amber-400 font-semibold' : 'hover:text-white' }}">Tổng quan</a>
                    <a href="{{ route('leads.index') }}" class="{{ request()->routeIs('leads.*') ? 'text-amber-400 font-semibold' : 'hover:text-white' }}">Quản lý Leads</a>
                    @can('admin')
                        <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'text-amber-400 font-semibold' : 'hover:text-white' }}">Users</a>
                    @endcan
                    <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'text-amber-400 font-semibold' : 'hover:text-white' }}">Tài khoản</a>
                </div>
            </div>

            <div class="sm:hidden bg-[#16365c] text-slate-200 text-sm px-5 py-3 space-y-2" x-show="navOpen" x-cloak>
                <form method="GET" action="{{ route('leads.index') }}">
                    <input class="ts-input" type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tên, SĐT, mã lead...">
                </form>
                <a class="block py-1" href="{{ route('dashboard') }}">Tổng quan</a>
                <a class="block py-1" href="{{ route('leads.index') }}">Quản lý Leads</a>
                @can('admin')
                    <a class="block py-1" href="{{ route('users.index') }}">Users</a>
                @endcan
            </div>

            <div class="ts-page">
                @if (session('status'))
                    <div class="ts-flash ts-flash-ok">{{ session('status') }}</div>
                @endif
                @if ($errors->any() && ! request()->routeIs('login'))
                    <div class="ts-flash ts-flash-err">{{ $errors->first() }}</div>
                @endif

                {{ $slot }}
            </div>

            <footer class="ts-footer">
                <div>CRM Telesale · Checkmate</div>
                <div>ieltscheckmate.com</div>
            </footer>
        </div>
    </body>
</html>
