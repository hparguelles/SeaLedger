<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $sale = DB::transaction(function () use ($data) {
            $sale = Sale::create(collect($data)->except('items')->all());
            $sale->items()->createMany($data['items']);

            return $sale;
        });

        Activity::record('Daily sales recorded', $data['sale_date']);

        return response()->json(Serializers::sale($sale->load('items')), 201);
    }

    public function update(Request $request, Sale $sale): JsonResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($sale, $data) {
            $sale->update(collect($data)->except('items')->all());
            $sale->items()->delete();
            $sale->items()->createMany($data['items']);
        });

        Activity::record('Daily sales record updated', $data['sale_date']);

        return response()->json(Serializers::sale($sale->fresh('items')));
    }

    public function destroy(Sale $sale): JsonResponse
    {
        $sale->delete();

        Activity::record('Daily sales record removed', $sale->sale_date->toDateString());

        return response()->json(['message' => 'Sales record deleted.']);
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
