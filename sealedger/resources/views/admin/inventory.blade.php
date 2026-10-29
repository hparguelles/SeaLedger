@extends('layouts.admin')

@section('title', 'Fish Inventory')

@section('content')
    <x-page-heading title="Fish Inventory">
        <button type="button" id="addProductButton" class="btn-primary">
            <i class="bi bi-plus-lg"></i>
            Add fish product
        </button>
    </x-page-heading>

    <section class="mb-[22px] grid grid-cols-2 gap-2.5 lg:grid-cols-4 lg:gap-4">
        <x-stat-card kicker="PRODUCTS" label="Total listed products">{{ $products->count() }}</x-stat-card>
        <x-stat-card kicker="STOCK" label="Available stock total">{{ number_format($totalStock, 1) }} <small class="text-sm font-semibold">kg</small></x-stat-card>
        <x-stat-card kicker="ALERTS" label="At or below minimum stock">{{ $lowCount }}</x-stat-card>
        <x-stat-card kicker="UNAVAILABLE" label="Unavailable products">{{ $unavailableCount }}</x-stat-card>
    </section>

    <section class="card">
        <div class="mb-[18px] flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="mb-[5px] text-base font-extrabold">Product list</h2>
                <p class="text-xs">Search, edit, and update your current fish stock</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <input type="text" id="inventorySearch" placeholder="Search fish..." class="field !w-auto min-w-[140px] flex-1 !py-1.5 !text-xs">
                <select id="inventoryFilter" class="field !w-auto min-w-[140px] flex-1 !py-1.5 !text-xs">
                    <option value="all">All products</option>
                    <option value="low">Low stock</option>
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fish product</th>
                        <th>Category</th>
                        <th>Price / kg</th>
                        <th>Stock (kg)</th>
                        <th>Minimum (kg)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="inventoryRows">
                    @foreach ($products as $product)
                        <tr data-search="{{ strtolower($product->name.' '.$product->category) }}"
                            data-low="{{ $product->is_low ? 1 : 0 }}"
                            data-available="{{ $product->is_available ? 1 : 0 }}">
                            <td>
                                @if ($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" class="mr-2 inline-block size-[38px] rounded-lg object-cover align-middle">
                                @else
                                    <span class="mr-2 inline-grid size-[38px] place-items-center rounded-lg bg-[#e4f5f2] align-middle text-[10px] font-extrabold text-brand">{{ $product->initials }}</span>
                                @endif
                                <strong>{{ $product->name }}</strong>
                            </td>
                            <td>{{ $product->category ?: '—' }}</td>
                            <td>₱{{ number_format($product->price, 2) }}</td>
                            <td>{{ number_format($product->stock, 1) }} kg</td>
                            <td>{{ number_format($product->min_stock, 1) }} kg</td>
                            <td><x-status-pill :label="$product->status_label" /></td>
                            <td>
                                <div class="flex gap-1">
                                    <button type="button"
                                            class="edit-product btn-light btn-sm"
                                            title="Edit"
                                            data-url="{{ route('inventory.update', $product) }}"
                                            data-product="{{ json_encode($product->only(['name', 'category', 'price', 'stock', 'min_stock', 'is_available']) + ['image_url' => $product->image_url]) }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <form method="POST" action="{{ route('inventory.toggle', $product) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-light btn-sm" title="Toggle availability">
                                            <i class="bi {{ $product->is_available ? 'bi-eye' : 'bi-eye-slash' }}"></i>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('inventory.destroy', $product) }}" data-confirm="Delete {{ $product->name }} from inventory?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-light btn-sm" title="Delete">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-empty-state id="inventoryEmpty" class="{{ $products->isEmpty() ? '' : 'hidden' }} mt-3">No products match your search.</x-empty-state>
        </div>

        <div id="inventoryCount" class="pt-3.5 text-[11px]"></div>
    </section>

    <x-modal id="productModal" title="Add fish product">
        <form id="productForm" method="POST" action="{{ route('inventory.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="PUT" disabled>

            <div class="grid gap-4 p-[22px] sm:grid-cols-2">
                <div>
                    <label for="productName" class="field-label">Fish name *</label>
                    <input type="text" id="productName" name="name" maxlength="60" required class="field">
                </div>
                <div>
                    <label for="productCategory" class="field-label">Category</label>
                    <input type="text" id="productCategory" name="category" maxlength="60" placeholder="e.g. Freshwater fish" class="field">
                </div>
                <div>
                    <label for="productPrice" class="field-label">Price per kg (₱) *</label>
                    <input type="number" id="productPrice" name="price" min="0" step="0.01" required class="field">
                </div>
                <div>
                    <label for="productStock" class="field-label">Stock (kg) *</label>
                    <input type="number" id="productStock" name="stock" min="0" step="0.1" required class="field">
                </div>
                <div>
                    <label for="productMinStock" class="field-label">Minimum stock (kg) *</label>
                    <input type="number" id="productMinStock" name="min_stock" min="0" step="0.1" required class="field">
                </div>
                <div class="sm:col-span-2">
                    <label for="productImage" class="field-label">Fish photo</label>
                    <div class="flex items-center gap-3">
                        <div id="productImagePreview" class="grid size-20 flex-none place-items-center overflow-hidden rounded-xl bg-[#e4f5f2] text-xl text-brand">
                            <i class="bi bi-image"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <input type="file" id="productImage" name="image" accept="image/jpeg,image/png,image/webp" class="field !py-1.5 text-xs">
                            <p class="mt-1 text-[11px]">JPG, PNG or WEBP, up to 5 MB. Customers see this photo.</p>
                            <label id="removeImageWrap" class="mt-1 hidden items-center gap-2 text-xs">
                                <input type="checkbox" id="removeImage" name="remove_image" value="1" class="size-3.5 accent-brand">
                                Remove current photo
                            </label>
                        </div>
                    </div>
                </div>
                <label class="flex items-center gap-2 sm:col-span-2">
                    <input type="checkbox" id="productAvailable" name="is_available" value="1" checked class="size-4 accent-brand">
                    <span class="text-sm">Product is available for sale</span>
                </label>
            </div>

            <div class="flex justify-end gap-2 border-t border-line px-[22px] py-[18px]">
                <button type="button" data-close-modal class="btn-light">Cancel</button>
                <button type="submit" class="btn-primary">Save product</button>
            </div>
        </form>
    </x-modal>
@endsection
