<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\RestockPlan;
use Illuminate\Database\Seeder;

class RestockPlanSeeder extends Seeder
{
    public function run(): void
    {
        if (RestockPlan::exists()) {
            return;
        }

        $plan = RestockPlan::create([
            'title' => 'Morning supply run',
            'plan_date' => now()->addDays(2)->toDateString(),
            'plan_time' => '04:30',
            'notes' => 'Buy fresh stock before market opens',
            'status' => 'Pending',
        ]);

        foreach ([['Bangus', 15], ['Tilapia', 10]] as [$name, $quantity]) {
            $plan->items()->create([
                'product_id' => Product::where('name', $name)->value('id'),
                'quantity' => $quantity,
            ]);
        }
    }
}
