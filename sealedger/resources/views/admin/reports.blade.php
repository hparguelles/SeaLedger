@extends('layouts.admin')

@section('title', 'Reports & History')

@section('content')
    <x-page-heading title="Reports & History">
        <button type="button" id="printReport" class="btn-outline">
            <i class="bi bi-printer"></i>
            Print report
        </button>
    </x-page-heading>

    <section class="card print:hidden">
        <div class="mb-[18px]">
            <h2 class="mb-[5px] text-base font-extrabold">Sales report period</h2>
            <p class="text-xs">Choose the dates included in the summary</p>
        </div>

        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[130px] flex-1 sm:min-w-[180px] sm:flex-none">
                <label for="reportFrom" class="field-label">From</label>
                <input type="date" id="reportFrom" name="from" value="{{ $from }}" class="field">
            </div>
            <div class="min-w-[130px] flex-1 sm:min-w-[180px] sm:flex-none">
                <label for="reportTo" class="field-label">To</label>
                <input type="date" id="reportTo" name="to" value="{{ $to }}" class="field">
            </div>
            <button type="submit" class="btn-primary">Apply filters</button>
            <a href="{{ route('reports.index') }}" class="btn-light">This month</a>
        </form>
    </section>

    <section class="mt-[18px] grid grid-cols-2 gap-2.5 lg:grid-cols-4 lg:gap-4">
        <x-stat-card kicker="SALES TOTAL" label="For selected period">₱{{ number_format($total, 2) }}</x-stat-card>
        <x-stat-card kicker="SALES RECORDS" label="Saved daily entries">{{ $sales->count() }}</x-stat-card>
        <x-stat-card kicker="DAILY AVERAGE" label="Per saved record">₱{{ number_format($average, 2) }}</x-stat-card>
        <x-stat-card kicker="LOW STOCK" label="Products at/below minimum">{{ $lowCount }}</x-stat-card>
    </section>

    <section class="card mt-[18px]">
        <div class="mb-[18px]">
            <h2 class="mb-[5px] text-base font-extrabold">Daily sales summary</h2>
            <p class="text-xs">Recorded end-of-day totals for the selected date range</p>
        </div>

        @if ($perDay->isEmpty())
            <x-empty-state>Record daily sales to see a chart here.</x-empty-state>
        @else
            <div class="flex min-h-[175px] items-end gap-3 overflow-x-auto px-1 py-5">
                @foreach ($perDay as $date => $value)
                    <div class="min-w-[55px] flex-1 text-center text-[10px]">
                        <div>₱{{ number_format($value, 2) }}</div>
                        <div class="mx-auto mb-2 mt-1.5 w-7 rounded-t-md bg-gradient-to-b from-[#28a6a4] to-brand" style="height: {{ max(5, ($value / $maxDay) * 120) }}px"></div>
                        <div class="whitespace-nowrap">{{ \Carbon\Carbon::parse($date)->format('M d') }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-3 overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Sales total</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td>{{ $sale->sale_date->format('M d, Y') }}</td>
                            <td><strong>₱{{ number_format($sale->amount, 2) }}</strong></td>
                            <td>{{ $sale->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="!py-6 text-center">No sales records in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-[18px] grid gap-[18px] lg:grid-cols-[1.1fr_.9fr]">
        <article class="card">
            <div class="mb-[18px]">
                <h2 class="mb-[5px] text-base font-extrabold">Current inventory summary</h2>
                <p class="text-xs">Available stock and minimum levels</p>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fish</th>
                            <th>Stock</th>
                            <th>Minimum</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td><strong>{{ $product->name }}</strong></td>
                                <td>{{ number_format($product->stock, 1) }} kg</td>
                                <td>{{ number_format($product->min_stock, 1) }} kg</td>
                                <td><x-status-pill :label="$product->status_label === 'Sold out' ? 'Low stock' : $product->status_label" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="!py-6 text-center">No products.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card">
            <div class="mb-[18px]">
                <h2 class="mb-[5px] text-base font-extrabold">Best-selling varieties</h2>
                <p class="text-xs">Based on optional quantity entries</p>
            </div>

            @forelse ($bestSellers as $seller)
                <div class="flex items-center gap-2.5 border-b border-line py-2.5 text-xs">
                    <span class="grid size-6 place-items-center rounded-[7px] bg-[#e5f5f3] font-extrabold text-brand">{{ $loop->iteration }}</span>
                    <span class="flex-1">{{ $seller->name }}</span>
                    <strong>{{ number_format($seller->kilos, 1) }} kg</strong>
                </div>
            @empty
                <x-empty-state>Optional quantities on sales records will appear here.</x-empty-state>
            @endforelse
        </article>
    </section>

    <section id="history" class="card mt-[18px]">
        <div class="mb-[18px] flex items-center justify-between gap-4">
            <div>
                <h2 class="mb-[5px] text-base font-extrabold">System activity history</h2>
                <p class="text-xs">Recent product, inventory, and restocking changes</p>
            </div>

            <form method="POST" action="{{ route('reports.history.clear') }}" data-confirm="Clear all activity history?" class="print:hidden">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-light btn-sm">Clear history</button>
            </form>
        </div>

        <div class="grid gap-[13px]">
            @forelse ($activities as $activity)
                <x-activity-item :title="$activity->text" :detail="$activity->detail" :time="$activity->created_at->format('M d, Y g:i A')" />
            @empty
                <p class="py-6 text-center">No history entries.</p>
            @endforelse
        </div>
    </section>
@endsection
