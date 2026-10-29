<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Product;
use App\Models\RestockPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class RestockingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $plan = DB::transaction(function () use ($data) {
            $items = $this->applyPhotos($data['items']);

            $plan = RestockPlan::create(collect($data)->except('items')->all() + ['status' => 'Pending']);
            $plan->items()->createMany($items);

            return $plan;
        });

        Activity::record('Restocking plan created', $data['title']);

        return response()->json(Serializers::plan($plan->load('items')), 201);
    }

    // POST (not PUT): the request can carry photos as multipart.
    public function update(Request $request, RestockPlan $restocking): JsonResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($restocking, $data) {
            $items = $this->applyPhotos($data['items']);

            $restocking->update(collect($data)->except('items')->all());
            $restocking->items()->delete();
            $restocking->items()->createMany($items);
        });

        Activity::record('Restocking plan updated', $data['title']);

        return response()->json(Serializers::plan($restocking->fresh('items')));
    }

    public function toggle(RestockPlan $restocking): JsonResponse
    {
        $completing = $restocking->status === 'Pending';

        $restocking->update(['status' => $completing ? 'Completed' : 'Pending']);

        Activity::record($completing ? 'Restocking plan completed' : 'Restocking plan reopened', $restocking->title);

        return response()->json(Serializers::plan($restocking->fresh('items')));
    }

    public function destroy(RestockPlan $restocking): JsonResponse
    {
        $restocking->delete();

        Activity::record('Restocking plan removed', $restocking->title);

        return response()->json(['message' => 'Plan deleted.']);
    }

    private function validated(Request $request): array
    {
        $request->merge([
            'items' => collect($request->input('items', []))
                ->filter(fn ($item) => ! empty($item['product_id']) && ! empty($item['quantity']))
                ->all(),
        ]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'plan_date' => ['required', 'date'],
            'plan_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.1'],
            'items.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $data['items'] ??= [];

        return $data;
    }

    /** A photo on a restock row becomes the product's photo (same as the web app). */
    private function applyPhotos(array $items): array
    {
        foreach ($items as &$item) {
            if (($item['image'] ?? null) instanceof UploadedFile) {
                Product::find($item['product_id'])?->replaceImage($item['image']);
            }

            unset($item['image']);
        }

        return array_values($items);
    }
}
