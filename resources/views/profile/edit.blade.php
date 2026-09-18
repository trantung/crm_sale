<x-app-layout title="Tài khoản">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 CRM Telesale <span>›</span> <strong>Tài khoản</strong>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="ts-panel p-6">
            @include('profile.partials.update-profile-information-form')
        </div>
        <div class="ts-panel p-6">
            @include('profile.partials.update-password-form')
        </div>
        <div class="ts-panel p-6 lg:col-span-2">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
