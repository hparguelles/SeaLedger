<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Delivery;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(): View
    {
        $deliveries = Delivery::with('items.product')
            ->orderByRaw("CASE WHEN status IN ('Delivered', 'Cancelled') THEN 1 ELSE 0 END")
            ->orderByRaw('estimated_at IS NULL')
            ->orderBy('estimated_at')
            ->orderByDesc('id')
            ->get();

        return view('admin.deliveries', [
            'deliveries' => $deliveries,
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'pendingCount' => $deliveries->where('status', Delivery::PENDING)->count(),
            'activeCount' => $deliveries->whereIn('status', [Delivery::CONFIRMED, Delivery::OUT])->count(),
            'deliveredCount' => $deliveries->where('status', Delivery::DELIVERED)->count(),
            'deliveredTotal' => $deliveries->where('status', Delivery::DELIVERED)->sum('amount'),
        ]);
    }

    /** Vendor manually types in an order received through Messenger. */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $delivery = Delivery::create(collect($data)->except('items')->all() + ['status' => Delivery::PENDING]);
            $delivery->items()->createMany($data['items']);
        });

        Activity::record('Delivery order received', $data['customer_name']);

        return redirect()->route('deliveries.index')->with('success', 'Delivery order saved.');
    }

    public function update(Request $request, Delivery $delivery): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($delivery, $data) {
            $delivery->update(collect($data)->except('items')->all());
            $delivery->items()->delete();
            $delivery->items()->createMany($data['items']);
        });

        Activity::record('Delivery order updated', $delivery->customer_name);

        return redirect()->route('deliveries.index')->with('success', 'Delivery order saved.');
    }

    /** Move an open order along: Pending -> Confirmed -> Out for delivery (or Cancelled). */
    public function status(Request $request, Delivery $delivery): RedirectResponse
    {
        if ($delivery->status === Delivery::DELIVERED) {
            return redirect()->route('deliveries.index')->withErrors('Delivered orders cannot be changed.');
        }

        $data = $request->validate([
            'status' => ['required', Rule::in([
                Delivery::PENDING,
                Delivery::CONFIRMED,
                Delivery::OUT,
                Delivery::CANCELLED,
            ])],
        ]);

        $delivery->update(['status' => $data['status']]);

        Activity::record('Delivery status: '.$data['status'], $delivery->customer_name);

        return redirect()->route('deliveries.index')->with('success', 'Delivery status updated.');
    }

    /**
     * Mark an order as Delivered. Needs the delivery time plus proof:
     * a delivery photo, or other accepted evidence written in the note.
     */
    public function deliver(Request $request, Delivery $delivery): RedirectResponse
    {
        if ($delivery->is_closed) {
            return redirect()->route('deliveries.index')->withErrors('This order is already closed.');
        }

        $data = $request->validate([
            'delivered_at' => ['required', 'date'],
            'proof_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'required_without:proof_note'],
            'proof_note' => ['nullable', 'string', 'max:500', 'required_without:proof_image'],
        ], [
            'proof_image.required_without' => 'Upload a delivery photo, or write the other proof (for example the customer\'s Messenger confirmation).',
            'proof_note.required_without' => 'Upload a delivery photo, or write the other proof (for example the customer\'s Messenger confirmation).',
        ]);

        $path = $request->hasFile('proof_image')
            ? $request->file('proof_image')->store('deliveries', 'public')
            : null;

        $delivery->update([
            'status' => Delivery::DELIVERED,
            'delivered_at' => $data['delivered_at'],
            'proof_path' => $path,
            'proof_note' => $data['proof_note'] ?? null,
        ]);

        // Saved to the transaction history shown in Reports & History.
        Activity::record(
            'Delivery completed',
            $delivery->customer_name.' · '.$delivery->delivered_at->format('M d, g:i A')
        );

        return redirect()->route('deliveries.index')->with('success', 'Order marked as delivered and saved to history.');
    }

    public function destroy(Delivery $delivery): RedirectResponse
    {
        if ($delivery->proof_path) {
            Storage::disk('public')->delete($delivery->proof_path);
        }

        $delivery->delete();

        Activity::record('Delivery order removed', $delivery->customer_name);

        return redirect()->route('deliveries.index')->with('success', 'Delivery order deleted.');
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
            'customer_name' => ['required', 'string', 'max:100'],
            'contact' => ['nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'estimated_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.1'],
        ]);

        $data['items'] ??= [];

        return $data;
    }
}
