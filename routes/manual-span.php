<?php

use Illuminate\Support\Facades\Route;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

Route::get('/test-manual-span', function () {
    $tracer = Globals::tracerProvider()->getTracer('manual-test');
    
    $span = $tracer->spanBuilder('manual-test-span')
        ->setSpanKind(SpanKind::KIND_SERVER)
        ->startSpan();
    
    $span->setAttributes([
        'test.manual' => true,
        'test.timestamp' => time(),
        'test.endpoint' => '/test-manual-span',
        'http.method' => 'GET',
        'http.url' => request()->fullUrl(),
    ]);
    
    // Simulate some work
    $span->addEvent('starting_work');
    usleep(100000); // 100ms
    $span->addEvent('work_completed');
    
    $span->setStatus(StatusCode::STATUS_OK, 'Manual span completed successfully');
    $span->end();
    
    return response()->json([
        'message' => 'Manual span created',
        'timestamp' => now(),
        'span_details' => [
            'tracer_class' => get_class($tracer),
            'span_class' => get_class($span),
        ]
    ]);
});
