<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RestockingController;
use App\Http\Controllers\Api\SaleController;
use Illuminate\Support\Facades\Route;

// Served under /api (see bootstrap/app.php). Used by the SeaLedger Android app.
// Only GET/POST/DELETE are used, so the app can talk to it with plain HttpURLConnection.

Route::get('/ping', fn () => response()->json(['app' => 'SeaLedger', 'ok' => true]));
Route::get('/catalog', [CatalogController::class, 'catalog']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth.token')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/password', [AuthController::class, 'changePassword'])->middleware('throttle:10,1');

    Route::get('/snapshot', [CatalogController::class, 'snapshot']);

    Route::post('/products', [ProductController::class, 'store']);
    Route::post('/products/{product}', [ProductController::class, 'update']);
    Route::post('/products/{product}/toggle', [ProductController::class, 'toggle']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    Route::post('/sales', [SaleController::class, 'store']);
    Route::post('/sales/{sale}', [SaleController::class, 'update']);
    Route::delete('/sales/{sale}', [SaleController::class, 'destroy']);

    Route::post('/restocking', [RestockingController::class, 'store']);
    Route::post('/restocking/{restocking}', [RestockingController::class, 'update']);
    Route::post('/restocking/{restocking}/toggle', [RestockingController::class, 'toggle']);
    Route::delete('/restocking/{restocking}', [RestockingController::class, 'destroy']);

    Route::delete('/activities', [ActivityController::class, 'clear']);
});
