<?php

use Illuminate\Support\Facades\Route;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\TracerInterface;

Route::get('/debug-otel', function () {
    $data = [];
    
    // Check extension
    $data['extension_loaded'] = extension_loaded('opentelemetry');
    
    // Check if tracer is available
    try {
        $tracer = Globals::tracerProvider()->getTracer('debug-test');
        $data['tracer_available'] = $tracer instanceof TracerInterface;
        $data['tracer_class'] = get_class($tracer);
        
        // Try to create a span
        $span = $tracer->spanBuilder('test-span')->startSpan();
        $data['span_created'] = true;
        $data['span_class'] = get_class($span);
        $span->end();
        
    } catch (Exception $e) {
        $data['tracer_error'] = $e->getMessage();
        $data['tracer_available'] = false;
    }
    
    // Check environment variables
    $data['env_vars'] = [
        'OTEL_PHP_AUTOLOAD_ENABLED' => env('OTEL_PHP_AUTOLOAD_ENABLED'),
        'OTEL_SERVICE_NAME' => env('OTEL_SERVICE_NAME'),
        'OTEL_EXPORTER_OTLP_ENDPOINT' => env('OTEL_EXPORTER_OTLP_ENDPOINT'),
        'OTEL_TRACES_EXPORTER' => env('OTEL_TRACES_EXPORTER'),
    ];
    
    // Check if classes exist
    $data['classes_exist'] = [
        'SymfonyInstrumentation' => class_exists('OpenTelemetry\Contrib\Instrumentation\Symfony\SymfonyInstrumentation'),
        'SDK' => class_exists('OpenTelemetry\SDK\Sdk'),
        'TracerProvider' => class_exists('OpenTelemetry\SDK\Trace\TracerProvider'),
    ];
    
    return response()->json($data, 200, [], JSON_PRETTY_PRINT);
});
