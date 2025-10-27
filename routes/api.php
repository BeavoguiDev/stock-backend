
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StockController;

// ✅ Route test
Route::get('/ping', function () {
    return response()->json(['message' => 'Communication avec API Laravel fonctionne !']);
})->middleware(\App\Http\Middleware\CorsMiddleware::class);
Route::get('/stocks', [StockController::class, 'index']);
