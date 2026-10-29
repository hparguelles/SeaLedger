<?php

namespace App\Providers;

use App\Models\Delivery;
use App\Models\RestockPlan;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        View::composer('layouts.admin', function ($view) {
            $view->with('pendingBadge', RestockPlan::pending()->count());
            $view->with('deliveryBadge', Delivery::open()->count());
        });
    }
}
