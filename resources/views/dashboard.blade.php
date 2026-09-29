<x-app-layout title="Tổng quan">
    <div class="ts-crumb">
        <div class="ts-crumb-path">
            📁 CRM Telesale <span>›</span> <strong>Tổng quan</strong>
        </div>
        <div class="ts-actions">
            <a href="{{ route('orders.create') }}" class="ts-btn ts-btn-primary">+ Tạo đơn hàng</a>
            <a href="{{ route('leads.create') }}" class="ts-btn ts-btn-ghost">+ Thêm Lead</a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <a href="{{ route('leads.index') }}" class="ts-stat">
            <div class="k">Tổng leads</div>
            <div class="v">{{ $totalLeads }}</div>
        </a>
        <a href="{{ route('leads.index', ['level' => 'L0']) }}" class="ts-stat">
            <div class="k">L0 - Mới</div>
            <div class="v">{{ $l0Count }}</div>
        </a>
        <a href="{{ route('leads.index', ['callback' => 1]) }}" class="ts-stat">
            <div class="k">Hẹn gọi lại</div>
            <div class="v">{{ $callbackCount }}</div>
        </a>
        <a href="{{ route('orders.index') }}" class="ts-stat">
            <div class="k">Doanh thu</div>
            <div class="v">{{ number_format($revenueTotal, 0, ',', '.') }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ number_format($orderCount) }} đơn hàng</div>
        </a>
        @if (Auth::user()->isAdmin())
            <a href="{{ route('leads.index', ['owner_id' => 'unassigned']) }}" class="ts-stat">
                <div class="k">Chưa phân TVV</div>
                <div class="v">{{ $unassignedCount }}</div>
            </a>
        @endif
    </div>

    <div class="ts-panel p-5">
        <div class="ts-card-title">Phễu level L0 – L6</div>
        <div class="grid grid-cols-2 md:grid-cols-7 gap-3">
            @foreach ($funnel as $bucket)
                <a href="{{ route('leads.index', ['level' => $bucket['level']]) }}" class="rounded-lg border border-slate-200 p-3 hover:border-[#0f2744]">
                    <div class="text-xs text-slate-500">{{ $bucket['name'] }}</div>
                    <div class="mt-1 text-xl font-extrabold text-[#0f2744]">{{ $bucket['count'] }}</div>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
