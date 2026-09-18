<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadStage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $base = Lead::query()->visibleTo($user);

        $funnel = LeadStage::query()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LeadStage $stage) => [
                'name' => $stage->name,
                'count' => (clone $base)->where('stage_id', $stage->id)->count(),
            ]);

        return view('dashboard', [
            'totalLeads' => (clone $base)->count(),
            'unassignedCount' => $user->isAdmin() ? Lead::query()->whereNull('owner_id')->count() : 0,
            'newCount' => (clone $base)->tab('new')->count(),
            'callbackCount' => (clone $base)->tab('callback')->count(),
            'recareCount' => (clone $base)->tab('recare')->count(),
            'funnel' => $funnel,
        ]);
    }
}
