<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Đăng nhập · CRM Telesale</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=be-vietnam-pro:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="ts-body antialiased">
        <div class="ts-login">
            <header class="ts-topbar">
                <div class="ts-brand"><span>CRM</span> TELESALE</div>
            </header>
            <div class="flex-1 flex items-center justify-center p-6">
                <div class="ts-login-card">
                    {{ $slot }}
                </div>
            </div>
            <footer class="ts-footer">
                <div>CRM Telesale · Checkmate</div>
                <div>ieltscheckmate.com</div>
            </footer>
        </div>
    </body>
</html>
