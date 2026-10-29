<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Product;
use App\Models\RestockPlan;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    /** Public: what customers see (available fish only). */
    public function catalog(): JsonResponse
    {
        return response()->json([
            'products' => Product::available()->orderBy('name')->get()->map([Serializers::class, 'product'])->values(),
        ]);
    }

    /** Vendor: everything the app needs in one request. The app polls this. */
    public function snapshot(): JsonResponse
    {
        return response()->json([
            'products' => Product::orderBy('name')->get()->map([Serializers::class, 'product'])->values(),
            'sales' => Sale::with('items')->orderByDesc('sale_date')->orderByDesc('id')->get()
                ->map([Serializers::class, 'sale'])->values(),
            'plans' => RestockPlan::with('items')->orderBy('plan_date')->orderBy('plan_time')->get()
                ->map([Serializers::class, 'plan'])->values(),
            'activities' => Activity::latest()->latest('id')->take(100)->get()
                ->map([Serializers::class, 'activity'])->values(),
        ]);
    }
}
