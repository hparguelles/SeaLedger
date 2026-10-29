@extends('layouts.admin')

@section('title', 'Delivery Service')

@section('content')
    <x-page-heading title="Delivery Service">
        <button type="button" id="addDeliveryButton" class="btn-primary">
            <i class="bi bi-plus-lg"></i>
            New delivery order
        </button>
    </x-page-heading>

    <section class="mb-[22px] grid grid-cols-2 gap-2.5 lg:grid-cols-4 lg:gap-4">
        <x-stat-card kicker="NEW ORDERS" label="Waiting for confirmation">{{ $pendingCount }}</x-stat-card>
        <x-stat-card kicker="IN PROGRESS" label="Confirmed or out for delivery">{{ $activeCount }}</x-stat-card>
        <x-stat-card kicker="DELIVERED" label="Completed orders">{{ $deliveredCount }}</x-stat-card>
        <x-stat-card kicker="DELIVERED VALUE" label="Total of delivered orders">₱{{ number_format($deliveredTotal, 2) }}</x-stat-card>
    </section>

    <section class="card mb-[18px]">
        <h2 class="mb-3 text-base font-extrabold">How delivery orders work</h2>
        <ol class="grid gap-3 text-xs sm:grid-cols-2 lg:grid-cols-4">
            <li class="rounded-xl bg-soft p-3"><strong class="block text-brand">1. Order received</strong>A customer messages you on Messenger. Type the order into <em>New delivery order</em>.</li>
            <li class="rounded-xl bg-soft p-3"><strong class="block text-brand">2. Confirm &amp; prepare</strong>Answer their questions, confirm the order and the estimated delivery time, then prepare it.</li>
            <li class="rounded-xl bg-soft p-3"><strong class="block text-brand">3. Out for delivery</strong>Update the status so you can see which orders are on the road.</li>
            <li class="rounded-xl bg-soft p-3"><strong class="block text-brand">4. Delivered</strong>Upload a delivery photo (or other proof), record the delivery time, and mark it Delivered. It is saved to history.</li>
        </ol>
    </section>

    <section class="card">
        <div class="mb-[18px] flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="mb-[5px] text-base font-extrabold">Delivery orders</h2>
                <p class="text-xs">Open orders first, then delivered and cancelled history</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <input type="text" id="deliverySearch" placeholder="Search customer or address..." class="field !w-auto min-w-[160px] flex-1 !py-1.5 !text-xs">
                <select id="deliveryFilter" class="field !w-auto min-w-[140px] flex-1 !py-1.5 !text-xs">
                    <option value="all">All orders</option>
                    <option value="open">Open orders</option>
                    @foreach (['Pending', 'Confirmed', 'Out for delivery', 'Delivered', 'Cancelled'] as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Address</th>
                        <th>Fish ordered</th>
                        <th>Amount</th>
                        <th>Delivery time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="deliveryRows">
                    @foreach ($deliveries as $delivery)
                        @php
                            $deliveryData = [
                                'customer_name' => $delivery->customer_name,
                                'contact' => $delivery->contact,
                                'address' => $delivery->address,
                                'amount' => $delivery->amount,
                                'estimated_at' => $delivery->estimated_at?->format('Y-m-d\TH:i'),
                                'notes' => $delivery->notes,
                                'items' => $delivery->items->map(fn ($item) => ['product_id' => $item->product_id, 'quantity' => $item->quantity])->all(),
                            ];
                        @endphp
                        <tr data-search="{{ strtolower($delivery->customer_name.' '.$delivery->contact.' '.$delivery->address) }}"
                            data-status="{{ $delivery->status }}"
                            data-open="{{ $delivery->is_closed ? 0 : 1 }}">
                            <td>
                                <strong>{{ $delivery->customer_name }}</strong>
                                @if ($delivery->contact)
                                    <div class="text-[11px]"><i class="bi bi-messenger"></i> {{ $delivery->contact }}</div>
                                @endif
                            </td>
                            <td class="min-w-[160px]">{{ $delivery->address }}</td>
                            <td class="min-w-[130px]">
                                @forelse ($delivery->items as $item)
                                    <div>{{ $item->product?->name ?? 'Removed' }}: {{ number_format($item->quantity, 1) }} kg</div>
                                @empty
                                    <span class="text-[11px]">Not specified</span>
                                @endforelse
                            </td>
                            <td>{{ $delivery->amount !== null ? '₱'.number_format($delivery->amount, 2) : '—' }}</td>
                            <td class="whitespace-nowrap">
                                @if ($delivery->status === 'Delivered')
                                    <span class="text-[11px]">Delivered</span><br>{{ $delivery->delivered_at->format('M d, g:i A') }}
                                @elseif ($delivery->estimated_at)
                                    <span class="text-[11px]">Estimated</span><br>{{ $delivery->estimated_at->format('M d, g:i A') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td><x-status-pill :label="$delivery->status" /></td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @if ($delivery->status === 'Pending')
                                        <form method="POST" action="{{ route('deliveries.status', $delivery) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="Confirmed">
                                            <button type="submit" class="btn-outline btn-sm">Confirm</button>
                                        </form>
                                    @elseif ($delivery->status === 'Confirmed')
                                        <form method="POST" action="{{ route('deliveries.status', $delivery) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="Out for delivery">
                                            <button type="submit" class="btn-outline btn-sm">Out for delivery</button>
                                        </form>
                                    @endif

                                    @unless ($delivery->is_closed)
                                        <button type="button"
                                                class="deliver-order btn-primary btn-sm"
                                                data-url="{{ route('deliveries.deliver', $delivery) }}"
                                                data-customer="{{ $delivery->customer_name }}">
                                            <i class="bi bi-check2-circle"></i> Delivered
                                        </button>

                                        <button type="button"
                                                class="edit-delivery btn-light btn-sm"
                                                title="Edit"
                                                data-url="{{ route('deliveries.update', $delivery) }}"
                                                data-delivery="{{ json_encode($deliveryData) }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <form method="POST" action="{{ route('deliveries.status', $delivery) }}" data-confirm="Cancel this delivery order?">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="Cancelled">
                                            <button type="submit" class="btn-light btn-sm" title="Cancel order"><i class="bi bi-slash-circle"></i></button>
                                        </form>
                                    @endunless

                                    @if ($delivery->status === 'Delivered' && ($delivery->proof_url || $delivery->proof_note))
                                        <button type="button"
                                                class="view-proof btn-light btn-sm"
                                                data-image="{{ $delivery->proof_url }}"
                                                data-note="{{ $delivery->proof_note }}"
                                                data-customer="{{ $delivery->customer_name }}">
                                            <i class="bi bi-patch-check"></i> Proof
                                        </button>
                                    @endif

                                    <form method="POST" action="{{ route('deliveries.destroy', $delivery) }}" data-confirm="Delete this delivery order and its history?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-light btn-sm" title="Delete"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-empty-state id="deliveryEmpty" class="{{ $deliveries->isEmpty() ? '' : 'hidden' }} mt-3">No delivery orders yet. Add one when a customer orders through Messenger.</x-empty-state>
        </div>

        <div id="deliveryCount" class="pt-3.5 text-[11px]"></div>
    </section>

    {{-- New / edit order --}}
    <x-modal id="deliveryModal" title="New delivery order">
        <form id="deliveryForm" method="POST" action="{{ route('deliveries.store') }}">
            @csrf
            <input type="hidden" name="_method" value="PUT" disabled>

            <div class="grid gap-4 p-[22px] sm:grid-cols-2">
                <div>
                    <label for="deliveryCustomer" class="field-label">Customer name *</label>
                    <input type="text" id="deliveryCustomer" name="customer_name" maxlength="100" required class="field">
                </div>
                <div>
                    <label for="deliveryContact" class="field-label">Messenger name / phone</label>
                    <input type="text" id="deliveryContact" name="contact" maxlength="100" class="field">
                </div>
                <div class="sm:col-span-2">
                    <label for="deliveryAddress" class="field-label">Delivery address *</label>
                    <input type="text" id="deliveryAddress" name="address" maxlength="255" required class="field">
                </div>
                <div>
                    <label for="deliveryEstimated" class="field-label">Estimated delivery time</label>
                    <input type="datetime-local" id="deliveryEstimated" name="estimated_at" class="field">
                </div>
                <div>
                    <label for="deliveryAmount" class="field-label">Order total (₱)</label>
                    <input type="number" id="deliveryAmount" name="amount" min="0" step="0.01" class="field">
                </div>

                <div class="sm:col-span-2">
                    <div class="mb-2 flex items-center justify-between">
                        <strong>Fish ordered</strong>
                        <button type="button" id="addDeliveryItem" class="btn-outline btn-sm">Add variety</button>
                    </div>
                    <div id="deliveryItems"></div>
                </div>

                <div class="sm:col-span-2">
                    <label for="deliveryNotes" class="field-label">Notes</label>
                    <textarea id="deliveryNotes" name="notes" rows="2" placeholder="Landmarks, cleaning request, payment arrangement..." class="field"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-line px-[22px] py-[18px]">
                <button type="button" data-close-modal class="btn-light">Cancel</button>
                <button type="submit" class="btn-primary">Save order</button>
            </div>
        </form>
    </x-modal>

    {{-- Mark delivered --}}
    <x-modal id="deliverModal" title="Mark as delivered">
        <form id="deliverForm" method="POST" action="#" enctype="multipart/form-data">
            @csrf

            <div class="grid gap-4 p-[22px]">
                <p class="text-xs">Order for <strong id="deliverCustomer"></strong></p>

                <div>
                    <label for="deliveredAt" class="field-label">Delivery time *</label>
                    <input type="datetime-local" id="deliveredAt" name="delivered_at" required class="field">
                </div>

                <div>
                    <label for="proofImage" class="field-label">Delivery photo</label>
                    <input type="file" id="proofImage" name="proof_image" accept="image/jpeg,image/png,image/webp" class="field !py-1.5 text-xs">
                    <p class="mt-1 text-[11px]">JPG, PNG or WEBP, up to 5 MB.</p>
                </div>

                <div>
                    <label for="proofNote" class="field-label">Other proof (if no photo)</label>
                    <textarea id="proofNote" name="proof_note" rows="2" maxlength="500" placeholder="e.g. Customer confirmed receipt on Messenger at 9:40 AM" class="field"></textarea>
                    <p class="mt-1 text-[11px]">A photo or a written proof is required.</p>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-line px-[22px] py-[18px]">
                <button type="button" data-close-modal class="btn-light">Cancel</button>
                <button type="submit" class="btn-primary">Mark delivered</button>
            </div>
        </form>
    </x-modal>

    {{-- View proof --}}
    <x-modal id="proofModal" title="Delivery proof">
        <div class="grid gap-3 p-[22px]">
            <p class="text-xs">Order for <strong id="proofCustomer"></strong></p>
            <img id="proofImg" src="" alt="Delivery proof" class="hidden max-h-[60vh] w-full rounded-xl object-contain">
            <p id="proofText" class="hidden rounded-lg bg-soft p-3 text-xs"></p>
        </div>
        <div class="flex justify-end border-t border-line px-[22px] py-[18px]">
            <button type="button" data-close-modal class="btn-light">Close</button>
        </div>
    </x-modal>

    <script type="text/template" id="deliveryItemTemplate">
        <div class="item-row mb-2 flex gap-2">
            <select name="items[__index__][product_id]" class="field min-w-0 flex-1">
                <option value="">Choose fish variety</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
            <input type="number" name="items[__index__][quantity]" min="0.1" step="0.1" placeholder="Kg" class="field max-w-[110px]">
            <button type="button" class="remove-item btn-light"><i class="bi bi-x-lg"></i></button>
        </div>
    </script>
@endsection
