@extends('layouts.admin')

@section('title', 'Restocking Plans')

@section('content')
    <x-page-heading title="Restocking Plans">
        <button type="button" id="addPlanButton" class="btn-primary">
            <i class="bi bi-plus-lg"></i>
            Create plan
        </button>
    </x-page-heading>

    <section class="mb-[22px] grid grid-cols-2 gap-2.5 lg:grid-cols-4 lg:gap-4">
        <x-stat-card kicker="ALL PLANS" label="Saved purchase plans">{{ $plans->count() }}</x-stat-card>
        <x-stat-card kicker="PENDING" label="Still to complete">{{ $pendingCount }}</x-stat-card>
        <x-stat-card kicker="COMPLETED" label="Finished plans">{{ $completedCount }}</x-stat-card>
        <x-stat-card kicker="NEXT PLAN" label="Upcoming date">{{ $nextPlan ? $nextPlan->plan_date->format('M d, Y') : '—' }}</x-stat-card>
    </section>

    <section class="card">
        <div class="mb-[18px] flex items-center justify-between gap-4">
            <div>
                <h2 class="mb-[5px] text-base font-extrabold">Purchase reminders</h2>
                <p class="text-xs">Plan your next supply run</p>
            </div>
            <select id="planFilter" class="field !w-auto max-w-[150px] !py-1.5 !text-xs">
                <option value="all">All plans</option>
                <option value="Pending">Pending</option>
                <option value="Completed">Completed</option>
            </select>
        </div>

        <div class="grid gap-[15px] md:grid-cols-2">
            @foreach ($plans as $plan)
                @php
                    $planData = [
                        'title' => $plan->title,
                        'plan_date' => $plan->plan_date->toDateString(),
                        'plan_time' => $plan->time_label,
                        'notes' => $plan->notes,
                        'items' => $plan->items->map(fn ($item) => ['product_id' => $item->product_id, 'quantity' => $item->quantity])->all(),
                    ];
                @endphp

                <article class="plan-card rounded-xl border border-line p-[18px]" data-status="{{ $plan->status }}">
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center gap-2 rounded-[9px] bg-[#e9f7f5] px-2.5 py-2 text-brand">
                            <strong class="text-xl">{{ $plan->plan_date->format('d') }}</strong>
                            {{ $plan->plan_date->format('M') }}
                        </span>
                        <x-status-pill :label="$plan->status" />
                    </div>

                    <h3 class="mb-2 mt-[15px] text-base font-extrabold">{{ $plan->title }}</h3>
                    <p class="text-xs"><i class="bi bi-calendar3"></i> {{ $plan->plan_date->format('M d, Y') }} · {{ $plan->time_label }}</p>

                    <div class="mt-3 border-t border-line pt-3">
                        <strong class="mb-2 block text-[11px]">Fish to buy</strong>
                        @forelse ($plan->items as $item)
                            <div class="flex justify-between py-1 text-xs">
                                <span>{{ $item->product?->name ?? 'Removed product' }}</span>
                                <b>{{ number_format($item->quantity, 1) }} kg</b>
                            </div>
                        @empty
                            <p class="text-xs">No varieties listed.</p>
                        @endforelse
                    </div>

                    @if ($plan->notes)
                        <p class="mt-2 text-xs">{{ $plan->notes }}</p>
                    @endif

                    <div class="mt-[15px] flex flex-wrap gap-[7px]">
                        <form method="POST" action="{{ route('restocking.toggle', $plan) }}">
                            @csrf
                            @method('PATCH')
                            @if ($plan->status === 'Pending')
                                <button type="submit" class="btn-outline btn-sm">Mark completed</button>
                            @else
                                <button type="submit" class="btn-light btn-sm">Reopen</button>
                            @endif
                        </form>

                        <button type="button"
                                class="edit-plan btn-light btn-sm"
                                data-url="{{ route('restocking.update', $plan) }}"
                                data-plan="{{ json_encode($planData) }}">
                            <i class="bi bi-pencil"></i>
                        </button>

                        <form method="POST" action="{{ route('restocking.destroy', $plan) }}" data-confirm="Delete this restocking plan?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-light btn-sm"><i class="bi bi-trash3"></i></button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        <x-empty-state id="planEmpty" class="{{ $plans->isEmpty() ? '' : 'hidden' }}">No plans found. Create a restocking plan to get started.</x-empty-state>
    </section>

    <x-modal id="planModal" title="Create restocking plan">
        <form id="planForm" method="POST" action="{{ route('restocking.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="PUT" disabled>

            <div class="p-[22px]">
                <div class="mb-4">
                    <label for="planTitle" class="field-label">Plan title *</label>
                    <input type="text" id="planTitle" name="title" maxlength="100" required class="field">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="planDate" class="field-label">Date *</label>
                        <input type="date" id="planDate" name="plan_date" required data-default="{{ now()->addDay()->toDateString() }}" class="field">
                    </div>
                    <div>
                        <label for="planTime" class="field-label">Time *</label>
                        <input type="time" id="planTime" name="plan_time" required class="field">
                    </div>
                </div>

                <div class="mb-2 mt-6 flex items-center justify-between">
                    <strong>Fish varieties and target quantity</strong>
                    <button type="button" id="addPlanItem" class="btn-outline btn-sm">Add variety</button>
                </div>

                <div id="planItems"></div>
                <p class="text-[11px]">A photo you attach here becomes the photo customers see for that fish. Leave it empty to keep the current one.</p>

                <label for="planNotes" class="field-label mt-4">Notes</label>
                <textarea id="planNotes" name="notes" rows="2" class="field"></textarea>
            </div>

            <div class="flex justify-end gap-2 border-t border-line px-[22px] py-[18px]">
                <button type="button" data-close-modal class="btn-light">Cancel</button>
                <button type="submit" class="btn-primary">Save plan</button>
            </div>
        </form>
    </x-modal>

    <script type="text/template" id="planItemTemplate">
        <div class="item-row mb-3 rounded-lg border border-line p-2">
            <div class="flex gap-2">
                <select name="items[__index__][product_id]" class="field min-w-0 flex-1">
                    <option value="">Choose fish variety</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                <input type="number" name="items[__index__][quantity]" min="0.1" step="0.1" placeholder="Target kg" class="field max-w-[145px]">
                <button type="button" class="remove-item btn-light"><i class="bi bi-x-lg"></i></button>
            </div>
            <label class="mt-2 flex items-center gap-2 text-[11px]">
                <i class="bi bi-image text-brand"></i>
                <span class="flex-none">Fish photo (optional):</span>
                <input type="file" name="items[__index__][image]" accept="image/jpeg,image/png,image/webp" class="min-w-0 flex-1 text-[11px]">
            </label>
        </div>
    </script>
@endsection
