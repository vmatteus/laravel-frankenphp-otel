<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenTelemetry Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for OpenTelemetry integration.
    |
    */

    'enabled' => env('OTEL_PHP_AUTOLOAD_ENABLED', false),

    'service' => [
        'name' => env('OTEL_SERVICE_NAME', config('app.name')),
        'version' => env('OTEL_SERVICE_VERSION', '1.0.0'),
        'namespace' => env('OTEL_SERVICE_NAMESPACE', 'default'),
        'environment' => env('APP_ENV', 'production'),
    ],

    'exporter' => [
        'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318'),
        'protocol' => env('OTEL_EXPORTER_OTLP_PROTOCOL', 'http/protobuf'),
        'headers' => env('OTEL_EXPORTER_OTLP_HEADERS', ''),
    ],

    'traces' => [
        'exporter' => env('OTEL_TRACES_EXPORTER', 'otlp'),
    ],

    'metrics' => [
        'exporter' => env('OTEL_METRICS_EXPORTER', 'otlp'),
    ],

    'logs' => [
        'exporter' => env('OTEL_LOGS_EXPORTER', 'otlp'),
    ],

    'propagators' => env('OTEL_PROPAGATORS', 'tracecontext,baggage'),

    'resource_attributes' => env('OTEL_RESOURCE_ATTRIBUTES', ''),

    'log_level' => env('OTEL_LOG_LEVEL', 'info'),
];
