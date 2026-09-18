<?php

namespace App\Providers;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::define('admin', fn (User $user) => $user->isAdmin());

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            if (! $user) {
                $view->with(['headerAlertCount' => 0, 'callbackDueCount' => 0, 'unassignedCount' => 0]);

                return;
            }

            $visible = Lead::query()->visibleTo($user);
            $callbackDue = (clone $visible)
                ->whereNotNull('callback_at')
                ->where('callback_at', '>=', now()->startOfDay())
                ->where('callback_at', '<=', now()->endOfDay())
                ->count();
            $unassigned = $user->isAdmin() ? Lead::query()->whereNull('owner_id')->count() : 0;

            $view->with([
                'headerAlertCount' => $callbackDue + $unassigned,
                'callbackDueCount' => $callbackDue,
                'unassignedCount' => $unassigned,
            ]);
        });
    }
}
