<?php

namespace App\Http\Controllers\Api;

use App\Models\Activity;
use App\Models\Product;
use App\Models\RestockPlan;
use App\Models\Sale;

/** Turns models into the plain JSON shapes the Android app expects. */
class Serializers
{
    public static function product(Product $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'category' => $p->category,
            'image_url' => $p->image_url, // built from the request host, so it works on the LAN
            'price' => (float) $p->price,
            'stock' => (float) $p->stock,
            'min_stock' => (float) $p->min_stock,
            'is_available' => (bool) $p->is_available,
        ];
    }

    public static function sale(Sale $s): array
    {
        return [
            'id' => $s->id,
            'sale_date' => $s->sale_date->toDateString(),
            'amount' => (float) $s->amount,
            'notes' => $s->notes,
            'items' => $s->items->map(fn ($i) => [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'quantity' => (float) $i->quantity,
            ])->values(),
        ];
    }

    public static function plan(RestockPlan $p): array
    {
        return [
            'id' => $p->id,
            'title' => $p->title,
            'plan_date' => $p->plan_date->toDateString(),
            'plan_time' => substr($p->plan_time, 0, 5),
            'notes' => $p->notes,
            'status' => $p->status,
            'items' => $p->items->map(fn ($i) => [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'quantity' => (float) $i->quantity,
            ])->values(),
        ];
    }

    public static function activity(Activity $a): array
    {
        return [
            'id' => $a->id,
            'text' => $a->text,
            'detail' => $a->detail,
            'created_at_ms' => $a->created_at->getTimestamp() * 1000,
        ];
    }
}
