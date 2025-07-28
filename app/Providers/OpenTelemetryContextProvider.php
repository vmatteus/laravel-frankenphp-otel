<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Common\Instrumentation\Globals;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\ContextStorageInterface;

class OpenTelemetryContextProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('otel.context.storage', function ($app) {
            return Context::storage();
        });

        $this->app->singleton('otel.tracer', function ($app) {
            return Globals::tracerProvider()->getTracer('laravel-app', '1.0.0');
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Helper method to get current context
     */
    public static function getCurrentContext(): ContextInterface
    {
        return Context::getCurrent();
    }

    /**
     * Helper method to create a child context with span
     */
    public static function createChildContext(string $spanName, array $attributes = []): array
    {
        $tracer = app('otel.tracer');
        $span = $tracer->spanBuilder($spanName)->startSpan();
        
        if (!empty($attributes)) {
            $span->setAttributes($attributes);
        }
        
        $context = Context::getCurrent()->withContextValue($span);
        
        return [$span, $context, $context->activate()];
    }

    /**
     * Helper method to safely end span and deactivate context
     */
    public static function endSpanContext($span, $scope): void
    {
        if ($scope) {
            $scope->detach();
        }
        if ($span) {
            $span->end();
        }
    }
}
