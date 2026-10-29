<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $sales = Sale::with('items.product')
            ->when($request->filled('from'), fn ($query) => $query->whereDate('sale_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('sale_date', '<=', $request->to))
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->get();

        return view('admin.sales', [
            'sales' => $sales,
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'overallTotal' => Sale::sum('amount'),
            'overallCount' => Sale::count(),
            'monthTotal' => Sale::whereYear('sale_date', now()->year)->whereMonth('sale_date', now()->month)->sum('amount'),
            'from' => $request->from,
            'to' => $request->to,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $sale = Sale::create(collect($data)->except('items')->all());
            $sale->items()->createMany($data['items']);
        });

        Activity::record('Daily sales recorded', $data['sale_date']);

        return redirect()->route('sales.index')->with('success', 'Sales record saved.');
    }

    public function update(Request $request, Sale $sale): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($sale, $data) {
            $sale->update(collect($data)->except('items')->all());
            $sale->items()->delete();
            $sale->items()->createMany($data['items']);
        });

        Activity::record('Daily sales record updated', $data['sale_date']);

        return redirect()->route('sales.index')->with('success', 'Sales record saved.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $sale->delete();

        Activity::record('Daily sales record removed', $sale->sale_date->toDateString());

        return redirect()->route('sales.index')->with('success', 'Sales record deleted.');
    }

    private function validated(Request $request): array
    {
        $request->merge([
            'items' => collect($request->input('items', []))
                ->filter(fn ($item) => ! empty($item['product_id']) && ! empty($item['quantity']))
                ->values()
                ->all(),
        ]);

        $data = $request->validate([
            'sale_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.1'],
        ]);

        $data['items'] ??= [];

        return $data;
    }
}
