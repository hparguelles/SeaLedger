<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $sales = Sale::whereDate('sale_date', '>=', $from)
            ->whereDate('sale_date', '<=', $to)
            ->orderBy('sale_date')
            ->get();

        $total = $sales->sum('amount');

        $perDay = $sales
            ->groupBy(fn ($sale) => $sale->sale_date->toDateString())
            ->map(fn ($day) => $day->sum('amount'))
            ->take(-14);

        $bestSellers = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereDate('sales.sale_date', '>=', $from)
            ->whereDate('sales.sale_date', '<=', $to)
            ->groupBy('products.id', 'products.name')
            ->selectRaw('products.name as name, SUM(sale_items.quantity) as kilos')
            ->orderByDesc('kilos')
            ->limit(5)
            ->get();

        return view('admin.reports', [
            'from' => $from,
            'to' => $to,
            'sales' => $sales->reverse()->values(),
            'total' => $total,
            'average' => $sales->count() ? $total / $sales->count() : 0,
            'perDay' => $perDay,
            'maxDay' => max(1, (float) $perDay->max()),
            'products' => Product::orderBy('name')->get(),
            'lowCount' => Product::lowStock()->count(),
            'bestSellers' => $bestSellers,
            'activities' => Activity::latest()->take(30)->get(),
        ]);
    }

    public function clearHistory(): RedirectResponse
    {
        Activity::query()->delete();

        return redirect()->route('reports.index')->with('success', 'History cleared.');
    }
}
