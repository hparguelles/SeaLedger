<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Product;
use App\Models\RestockPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RestockingController extends Controller
{
    public function index(): View
    {
        $plans = RestockPlan::with('items.product')
            ->orderBy('plan_date')
            ->orderBy('plan_time')
            ->get();

        $pending = $plans->where('status', 'Pending');

        return view('admin.restocking', [
            'plans' => $plans,
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'pendingCount' => $pending->count(),
            'completedCount' => $plans->where('status', 'Completed')->count(),
            'nextPlan' => $pending->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $items = $this->applyPhotos($data['items']);

            $plan = RestockPlan::create(collect($data)->except('items')->all() + ['status' => 'Pending']);
            $plan->items()->createMany($items);
        });

        Activity::record('Restocking plan created', $data['title']);

        return redirect()->route('restocking.index')->with('success', 'Restocking plan saved.');
    }

    public function update(Request $request, RestockPlan $restocking): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($restocking, $data) {
            $items = $this->applyPhotos($data['items']);

            $restocking->update(collect($data)->except('items')->all());
            $restocking->items()->delete();
            $restocking->items()->createMany($items);
        });

        Activity::record('Restocking plan updated', $data['title']);

        return redirect()->route('restocking.index')->with('success', 'Restocking plan saved.');
    }

    public function toggle(RestockPlan $restocking): RedirectResponse
    {
        $completing = $restocking->status === 'Pending';

        $restocking->update(['status' => $completing ? 'Completed' : 'Pending']);

        Activity::record($completing ? 'Restocking plan completed' : 'Restocking plan reopened', $restocking->title);

        return redirect()->route('restocking.index')->with('success', 'Plan status updated.');
    }

    public function destroy(RestockPlan $restocking): RedirectResponse
    {
        $restocking->delete();

        Activity::record('Restocking plan removed', $restocking->title);

        return redirect()->route('restocking.index')->with('success', 'Plan deleted.');
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

    /**
     * A restocking row can carry a fresh photo of the fish. Save it as the
     * product's photo (so customers see it) and strip the file from the row
     * before the row is written to the database.
     */
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
