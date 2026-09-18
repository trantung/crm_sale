<x-app-layout title="Tổng quan">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 CRM Telesale <span>›</span> <strong>Tổng quan</strong>
        </div>
        <div class="ts-actions">
            <a href="{{ route('leads.create') }}" class="ts-btn ts-btn-primary">+ Thêm Lead</a>
            <a href="{{ route('leads.index') }}" class="ts-btn ts-btn-ghost">Danh sách Lead</a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <a href="{{ route('leads.index') }}" class="ts-stat">
            <div class="k">Tổng leads</div>
            <div class="v">{{ $totalLeads }}</div>
        </a>
        <a href="{{ route('leads.index', ['tab' => 'new']) }}" class="ts-stat">
            <div class="k">🔥 Lead mới</div>
            <div class="v">{{ $newCount }}</div>
        </a>
        <a href="{{ route('leads.index', ['tab' => 'callback']) }}" class="ts-stat">
            <div class="k">⏰ Hẹn gọi lại</div>
            <div class="v">{{ $callbackCount }}</div>
        </a>
        <a href="{{ route('leads.index', ['tab' => 'recare']) }}" class="ts-stat">
            <div class="k">❌ Chăm sóc lại</div>
            <div class="v">{{ $recareCount }}</div>
        </a>
        @if (Auth::user()->isAdmin())
            <a href="{{ route('leads.index', ['owner_id' => 'unassigned']) }}" class="ts-stat">
                <div class="k">Chưa phân sale</div>
                <div class="v">{{ $unassignedCount }}</div>
            </a>
        @endif
    </div>

    <div class="ts-panel p-5">
        <div class="ts-card-title">Phễu lead</div>
        <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
            @foreach ($funnel as $bucket)
                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="text-xs text-slate-500">{{ $bucket['name'] }}</div>
                    <div class="mt-1 text-xl font-extrabold text-[#0f2744]">{{ $bucket['count'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
