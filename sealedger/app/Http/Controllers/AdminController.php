<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Product;
use App\Models\RestockPlan;
use App\Models\Sale;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $lowStock = Product::lowStock()->orderBy('stock')->get();

        return view('admin.dashboard', [
            'activeProducts' => Product::available()->count(),
            'totalStock' => Product::available()->sum('stock'),
            'lowCount' => $lowStock->count(),
            'lowProducts' => $lowStock->take(6),
            'pendingCount' => RestockPlan::pending()->count(),
            'upcomingPlans' => RestockPlan::pending()->orderBy('plan_date')->orderBy('plan_time')->take(4)->get(),
            'todaySales' => Sale::whereDate('sale_date', today())->sum('amount'),
            'activities' => Activity::latest()->take(4)->get(),
        ]);
    }
}
