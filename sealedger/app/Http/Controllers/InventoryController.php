<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $products = Product::orderBy('name')->get();

        return view('admin.inventory', [
            'products' => $products,
            'totalStock' => $products->where('is_available', true)->sum('stock'),
            'lowCount' => $products->filter->is_low->count(),
            'unavailableCount' => $products->where('is_available', false)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $product = Product::create($data);

        if ($request->hasFile('image')) {
            $product->replaceImage($request->file('image'));
        }

        Activity::record('Product added', $product->name);

        return redirect()->route('inventory.index')->with('success', 'Product saved successfully.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request));

        if ($request->hasFile('image')) {
            $product->replaceImage($request->file('image'));
        } elseif ($request->boolean('remove_image')) {
            $product->replaceImage(null);
        }

        Activity::record('Product updated', $product->name);

        return redirect()->route('inventory.index')->with('success', 'Product saved successfully.');
    }

    public function toggle(Product $product): RedirectResponse
    {
        $product->update(['is_available' => ! $product->is_available]);

        Activity::record('Product availability changed', $product->name);

        return redirect()->route('inventory.index')->with('success', 'Product status updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        Activity::record('Product removed', $product->name);

        return redirect()->route('inventory.index')->with('success', 'Product removed.');
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

        // The photo is stored separately (see Product::replaceImage).
        unset($data['image']);

        $data['is_available'] = $request->boolean('is_available');

        return $data;
    }
}
