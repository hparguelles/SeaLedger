<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $product = Product::create($this->validated($request));

        if ($request->hasFile('image')) {
            $product->replaceImage($request->file('image'));
        }

        Activity::record('Product added', $product->name);

        return response()->json(Serializers::product($product->fresh()), 201);
    }

    // POST (not PUT) on purpose: PHP does not parse multipart bodies on PUT/PATCH,
    // and this endpoint can carry a photo.
    public function update(Request $request, Product $product): JsonResponse
    {
        $product->update($this->validated($request));

        if ($request->hasFile('image')) {
            $product->replaceImage($request->file('image'));
        } elseif ($request->boolean('remove_image')) {
            $product->replaceImage(null);
        }

        Activity::record('Product updated', $product->name);

        return response()->json(Serializers::product($product->fresh()));
    }

    public function toggle(Product $product): JsonResponse
    {
        $product->update(['is_available' => ! $product->is_available]);

        Activity::record('Product availability changed', $product->name);

        return response()->json(Serializers::product($product->fresh()));
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        Activity::record('Product removed', $product->name);

        return response()->json(['message' => 'Product removed.']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'category' => ['nullable', 'string', 'max:60'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        unset($data['image']);

        $data['is_available'] = $request->boolean('is_available');

        return $data;
    }
}
