<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SemConv\ResourceAttributes;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;

class OpenTelemetryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (config('opentelemetry.enabled', false)) {
            $this->initializeOpenTelemetry();
        }
    }

    /**
     * Initialize OpenTelemetry SDK
     */
    private function initializeOpenTelemetry(): void
    {
        if (!extension_loaded('opentelemetry')) {
            Log::warning('OpenTelemetry extension not loaded, skipping initialization');
            return;
        }

        try {
            // Create resource info
            $resource = ResourceInfoFactory::defaultResource()->merge(
                ResourceInfo::create(Attributes::create([
                    ResourceAttributes::SERVICE_NAME => env('OTEL_SERVICE_NAME', 'frankenphp-laravel'),
                    ResourceAttributes::SERVICE_VERSION => env('OTEL_SERVICE_VERSION', '1.0.0'),
                    ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => env('OTEL_ENVIRONMENT', config('app.env')),
                ]))
            );

            // Create OTLP exporter
            $endpoint = env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://host.docker.internal:4318');
            $transport = PsrTransportFactory::discover()->create($endpoint . '/v1/traces', 'application/json');
            $exporter = new SpanExporter($transport);

            // Create tracer provider
            $tracerProvider = TracerProvider::builder()
                ->addSpanProcessor(BatchSpanProcessor::builder($exporter)->build())
                ->setResource($resource)
                ->build();

            // Initialize SDK
            Sdk::builder()
                ->setTracerProvider($tracerProvider)
                ->setAutoShutdown(true)
                ->buildAndRegisterGlobal();

            Log::info('OpenTelemetry SDK initialized successfully', [
                'service_name' => env('OTEL_SERVICE_NAME', 'frankenphp-laravel'),
                'endpoint' => $endpoint,
                'environment' => env('OTEL_ENVIRONMENT', config('app.env'))
            ]);

        } catch (\Exception $e) {
            Log::error('OpenTelemetry SDK initialization failed: ' . $e->getMessage());
        }
    }
}
