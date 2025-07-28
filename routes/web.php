<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Controllers\ProductController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/otel-test', function () {
    // Log to help with debugging
    Log::info('OpenTelemetry test route accessed');
    
    return response()->json([
        'message' => 'OpenTelemetry test endpoint',
        'timestamp' => now(),
        'otel_enabled' => env('OTEL_PHP_AUTOLOAD_ENABLED', false),
        'otel_endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT'),
        'service_name' => env('OTEL_SERVICE_NAME'),
    ]);
});

// Product API routes for testing OpenTelemetry instrumentation
Route::prefix('api')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
});

// Include debug routes
require __DIR__ . '/debug-otel.php';
require __DIR__ . '/manual-span.php';
