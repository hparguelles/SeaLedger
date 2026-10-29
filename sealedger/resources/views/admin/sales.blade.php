@extends('layouts.admin')

@section('title', 'Daily Sales')

@section('content')
    <x-page-heading title="Daily Sales">
        <button type="button" id="newSaleButton" class="btn-primary">
            <i class="bi bi-plus-lg"></i>
            Record daily sales
        </button>
    </x-page-heading>

    <section class="mb-[22px] grid grid-cols-2 gap-2.5 lg:grid-cols-3 lg:gap-4">
        <x-stat-card kicker="TOTAL RECORDED" label="Across all saved records">₱{{ number_format($overallTotal, 2) }}</x-stat-card>
        <x-stat-card kicker="RECORDS" label="Daily entries saved">{{ $overallCount }}</x-stat-card>
        <x-stat-card kicker="THIS MONTH" label="Current month total">₱{{ number_format($monthTotal, 2) }}</x-stat-card>
    </section>

    <section class="card">
        <div class="mb-[18px] flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="mb-[5px] text-base font-extrabold">Sales records</h2>
                <p class="text-xs">Filter and correct daily totals as needed</p>
            </div>
            <form id="salesFilterForm" method="GET" action="{{ route('sales.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="date" id="salesFrom" name="from" value="{{ $from }}" aria-label="From date" class="field !w-auto flex-1 !py-1.5 !text-xs">
                <input type="date" id="salesTo" name="to" value="{{ $to }}" aria-label="To date" class="field !w-auto flex-1 !py-1.5 !text-xs">
                <a href="{{ route('sales.index') }}" class="btn-light btn-sm">Clear</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Sales total</th>
                        <th>Optional fish quantities</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        @php
                            $saleData = [
                                'sale_date' => $sale->sale_date->toDateString(),
                                'amount' => $sale->amount,
                                'notes' => $sale->notes,
                                'items' => $sale->items->map(fn ($item) => ['product_id' => $item->product_id, 'quantity' => $item->quantity])->all(),
                            ];
                        @endphp
                        <tr>
                            <td><strong>{{ $sale->sale_date->format('M d, Y') }}</strong></td>
                            <td>₱{{ number_format($sale->amount, 2) }}</td>
                            <td>
                                @forelse ($sale->items as $item)
                                    {{ $item->product?->name ?? 'Removed' }}: {{ number_format($item->quantity, 1) }} kg{{ $loop->last ? '' : ',' }}
                                @empty
                                    <span class="text-[11px]">Not specified</span>
                                @endforelse
                            </td>
                            <td>{{ $sale->notes ?: '—' }}</td>
                            <td>
                                <div class="flex gap-1">
                                    <button type="button"
                                            class="edit-sale btn-light btn-sm"
                                            data-url="{{ route('sales.update', $sale) }}"
                                            data-sale="{{ json_encode($saleData) }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <form method="POST" action="{{ route('sales.destroy', $sale) }}" data-confirm="Delete this sales record?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-light btn-sm"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="!py-6 text-center">No sales records for this date range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-3.5 text-[11px]">Showing {{ $sales->count() }} of {{ $overallCount }} records</div>
    </section>

    <x-modal id="saleModal" title="Record daily sales">
        <form id="saleForm" method="POST" action="{{ route('sales.store') }}">
            @csrf
            <input type="hidden" name="_method" value="PUT" disabled>

            <div class="p-[22px]">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="saleDate" class="field-label">Sales date *</label>
                        <input type="date" id="saleDate" name="sale_date" required data-default="{{ now()->toDateString() }}" class="field">
                    </div>
                    <div>
                        <label for="saleAmount" class="field-label">Total sales (₱) *</label>
                        <input type="number" id="saleAmount" name="amount" min="0" step="0.01" required class="field">
                    </div>
                </div>

                <div class="mb-2 mt-6 flex items-center justify-between">
                    <strong>Optional fish quantities</strong>
                    <button type="button" id="addSaleItem" class="btn-outline btn-sm">Add variety</button>
                </div>

                <div id="saleItems"></div>

                <label for="saleNotes" class="field-label mt-4">Notes</label>
                <textarea id="saleNotes" name="notes" rows="2" placeholder="Optional end-of-day notes" class="field"></textarea>

                <p class="mt-2 text-[11px]">Saving a sales record does not automatically deduct inventory stock.</p>
            </div>

            <div class="flex justify-end gap-2 border-t border-line px-[22px] py-[18px]">
                <button type="button" data-close-modal class="btn-light">Cancel</button>
                <button type="submit" class="btn-primary">Save record</button>
            </div>
        </form>
    </x-modal>

    <script type="text/template" id="saleItemTemplate">
        <div class="item-row mb-2 flex gap-2">
            <select name="items[__index__][product_id]" class="field min-w-0 flex-1">
                <option value="">Choose fish variety</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
            <input type="number" name="items[__index__][quantity]" min="0.1" step="0.1" placeholder="Quantity kg" class="field max-w-[145px]">
            <button type="button" class="remove-item btn-light"><i class="bi bi-x-lg"></i></button>
        </div>
    </script>
@endsection
