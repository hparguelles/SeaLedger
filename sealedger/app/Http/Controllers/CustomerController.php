<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $products = Product::available()->orderBy('name')->get();

        return view('customer.home', [
            'products' => $products,
            'categories' => $products->pluck('category')->filter()->unique()->sort()->values(),
        ]);
    }
}
