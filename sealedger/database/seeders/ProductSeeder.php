<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $fish = [
            ['name' => 'Bangus', 'category' => 'Milkfish', 'price' => 180, 'stock' => 4, 'min_stock' => 10],
            ['name' => 'Tilapia', 'category' => 'Freshwater fish', 'price' => 140, 'stock' => 3, 'min_stock' => 8],
            ['name' => 'Galunggong', 'category' => 'Round scad', 'price' => 160, 'stock' => 2, 'min_stock' => 6],
            ['name' => 'Tulingan', 'category' => 'Mackerel tuna', 'price' => 190, 'stock' => 14, 'min_stock' => 5],
            ['name' => 'Tamban', 'category' => 'Sardine', 'price' => 120, 'stock' => 25, 'min_stock' => 8],
            ['name' => 'Pampano', 'category' => 'Pomfret', 'price' => 280, 'stock' => 18, 'min_stock' => 5],
            ['name' => 'Lapu-lapu', 'category' => 'Grouper', 'price' => 420, 'stock' => 9, 'min_stock' => 4],
            ['name' => 'Dalagang Bukid', 'category' => 'Fusilier', 'price' => 230, 'stock' => 12, 'min_stock' => 5],
            ['name' => 'Hipon', 'category' => 'Shrimp', 'price' => 350, 'stock' => 7, 'min_stock' => 3],
            ['name' => 'Pusit', 'category' => 'Squid', 'price' => 300, 'stock' => 0, 'min_stock' => 4],
        ];

        foreach ($fish as $item) {
            Product::updateOrCreate(['name' => $item['name']], $item + ['is_available' => true]);
        }

        Activity::record('SeaLedger started', 'Sample fish products loaded');
    }
}
