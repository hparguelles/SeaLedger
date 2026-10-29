@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <x-page-heading title="Dashboard">
        <a href="{{ route('inventory.index') }}" class="btn-primary">
            <i class="bi bi-box-seam"></i>
            Manage inventory
        </a>
    </x-page-heading>

    <section class="mb-[22px] grid grid-cols-2 gap-2.5 lg:grid-cols-4 lg:gap-4">
        <x-stat-card kicker="ACTIVE PRODUCTS" label="Fish varieties available">{{ $activeProducts }}</x-stat-card>
        <x-stat-card kicker="CURRENT STOCK" label="Recorded available stock">{{ number_format($totalStock, 1) }} <small class="text-sm font-semibold">kg</small></x-stat-card>
        <x-stat-card kicker="LOW STOCK" label="At or below minimum">{{ $lowCount }}</x-stat-card>
        <x-stat-card kicker="PENDING PLANS" label="Upcoming restocking reminders">{{ $pendingCount }}</x-stat-card>
    </section>

    <section class="mb-[18px] grid gap-[18px] lg:grid-cols-[1.1fr_.9fr]">
        <article class="card">
            <div class="mb-[18px] flex items-center justify-between gap-4">
                <div>
                    <h2 class="mb-[5px] text-base font-extrabold">Low-stock alerts</h2>
                    <p class="text-xs">Products at or below their minimum stock level</p>
                </div>
                <a href="{{ route('inventory.index') }}" class="whitespace-nowrap text-xs font-bold text-brand">View inventory →</a>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fish product</th>
                            <th>Stock</th>
                            <th>Minimum</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lowProducts as $product)
                            <tr>
                                <td><strong>{{ $product->name }}</strong></td>
                                <td>{{ number_format($product->stock, 1) }} kg</td>
                                <td>{{ number_format($product->min_stock, 1) }} kg</td>
                                <td><x-status-pill :label="$product->status_label" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="!py-6 text-center">No low-stock products.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card">
            <div class="mb-[18px] flex items-center justify-between gap-4">
                <div>
                    <h2 class="mb-[5px] text-base font-extrabold">Upcoming restocking</h2>
                    <p class="text-xs">Your next supply reminders</p>
                </div>
                <a href="{{ route('restocking.index') }}" class="whitespace-nowrap text-xs font-bold text-brand">View plans →</a>
            </div>

            <div class="grid gap-[13px]">
                @forelse ($upcomingPlans as $plan)
                    <x-activity-item icon="bi-calendar-event" :title="$plan->title" :detail="$plan->plan_date->format('M d, Y').' · '.$plan->time_label" />
                @empty
                    <p class="text-[11px]">No pending plans.</p>
                @endforelse
            </div>

            <a href="{{ route('restocking.index') }}" class="btn-outline mt-4 w-full">Create a plan</a>
        </article>
    </section>

    <section class="grid gap-[18px] lg:grid-cols-[1.1fr_.9fr]">
        <article class="card">
            <div class="mb-[18px] flex items-center justify-between gap-4">
                <div>
                    <h2 class="mb-[5px] text-base font-extrabold">Today's sales</h2>
                    <p class="text-xs">End-of-day totals recorded today</p>
                </div>
                <a href="{{ route('sales.index') }}" class="whitespace-nowrap text-xs font-bold text-brand">View sales →</a>
            </div>

            <div class="text-[32px] font-extrabold tracking-tight">₱{{ number_format($todaySales, 2) }}</div>

            <a href="{{ route('sales.index') }}" class="btn-primary mt-4">Record daily sales</a>
        </article>

        <article class="card">
            <div class="mb-[18px] flex items-center justify-between gap-4">
                <div>
                    <h2 class="mb-[5px] text-base font-extrabold">Recent activity</h2>
                    <p class="text-xs">Latest system changes</p>
                </div>
                <a href="{{ route('reports.index') }}#history" class="whitespace-nowrap text-xs font-bold text-brand">View history →</a>
            </div>

            <div class="grid gap-[13px]">
                @forelse ($activities as $activity)
                    <x-activity-item :title="$activity->text" :detail="$activity->detail" :time="$activity->created_at->format('M d, Y g:i A')" />
                @empty
                    <p class="text-[11px]">No activity recorded.</p>
                @endforelse
            </div>
        </article>
    </section>
@endsection
