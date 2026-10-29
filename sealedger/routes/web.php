<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RestockingController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CustomerController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::patch('/inventory/{product}/toggle', [InventoryController::class, 'toggle'])->name('inventory.toggle');
        Route::resource('inventory', InventoryController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['inventory' => 'product']);

        Route::patch('/restocking/{restocking}/toggle', [RestockingController::class, 'toggle'])->name('restocking.toggle');
        Route::resource('restocking', RestockingController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('sales', SaleController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::patch('/deliveries/{delivery}/status', [DeliveryController::class, 'status'])->name('deliveries.status');
        Route::post('/deliveries/{delivery}/deliver', [DeliveryController::class, 'deliver'])->name('deliveries.deliver');
        Route::resource('deliveries', DeliveryController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::delete('/reports/history', [ReportController::class, 'clearHistory'])->name('reports.history.clear');
    });
});
