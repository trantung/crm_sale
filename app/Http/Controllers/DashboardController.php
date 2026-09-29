<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $base = Lead::query()->visibleTo($user);
        $orders = Order::query()->visibleTo($user);

        $funnel = collect(LeadStage::levelTabs())->map(fn (string $name, string $level) => [
            'level' => $level,
            'name' => $name,
            'count' => (clone $base)->level($level)->count(),
        ]);

        return view('dashboard', [
            'totalLeads' => (clone $base)->count(),
            'unassignedCount' => $user->isAdmin() ? Lead::query()->whereNull('owner_id')->count() : 0,
            'l0Count' => (clone $base)->level('L0')->count(),
            'callbackCount' => (clone $base)->tab('callback')->count(),
            'orderCount' => (clone $orders)->count(),
            'revenueTotal' => (clone $orders)->sum('total'),
            'funnel' => $funnel,
        ]);
    }
}
