<?php

// OpenTelemetry Auto-Instrumentation Bootstrap
// This file enables OpenTelemetry auto-instrumentation for Laravel

// Only initialize if OpenTelemetry is enabled
if (env('OTEL_PHP_AUTOLOAD_ENABLED', false)) {
    try {
        // Set environment variables that the auto-instrumentation will pick up
        if (!getenv('OTEL_SERVICE_NAME')) {
            putenv('OTEL_SERVICE_NAME=' . env('OTEL_SERVICE_NAME', 'laravel-app'));
        }
        
        if (!getenv('OTEL_SERVICE_VERSION')) {
            putenv('OTEL_SERVICE_VERSION=' . env('OTEL_SERVICE_VERSION', '1.0.0'));
        }
        
        if (!getenv('OTEL_EXPORTER_OTLP_ENDPOINT')) {
            putenv('OTEL_EXPORTER_OTLP_ENDPOINT=' . env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318'));
        }
        
        if (!getenv('OTEL_EXPORTER_OTLP_PROTOCOL')) {
            putenv('OTEL_EXPORTER_OTLP_PROTOCOL=' . env('OTEL_EXPORTER_OTLP_PROTOCOL', 'http/protobuf'));
        }
        
        if (!getenv('OTEL_TRACES_EXPORTER')) {
            putenv('OTEL_TRACES_EXPORTER=' . env('OTEL_TRACES_EXPORTER', 'otlp'));
        }
        
        if (!getenv('OTEL_PROPAGATORS')) {
            putenv('OTEL_PROPAGATORS=' . env('OTEL_PROPAGATORS', 'tracecontext,baggage'));
        }

        // Log that we're enabling OpenTelemetry
        error_log('OpenTelemetry auto-instrumentation enabled for service: ' . env('OTEL_SERVICE_NAME', 'laravel-app'));

    } catch (\Exception $e) {
        error_log('Failed to set up OpenTelemetry environment: ' . $e->getMessage());
    }
}
