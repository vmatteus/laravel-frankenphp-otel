<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use OpenTelemetry\Context\Context;
use OpenTelemetry\API\Common\Instrumentation\Globals;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use Symfony\Component\HttpFoundation\Response;

class OpenTelemetryContextMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get tracer
        $tracer = Globals::tracerProvider()->getTracer('laravel-middleware');
        
        // Create root span for the request
        $span = $tracer->spanBuilder('http.request')
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->startSpan();
            
        // Activate the span in the context
        $scope = $span->activate();
        
        try {
            // Set HTTP attributes
            $span->setAttributes([
                'http.method' => $request->method(),
                'http.url' => $request->fullUrl(),
                'http.route' => $request->route() ? $request->route()->uri() : 'unknown',
                'http.user_agent' => $request->userAgent(),
                'middleware.layer' => 'http',
            ]);
            
            // Add event for request start
            $span->addEvent('request.start');
            
            // Process the request
            $response = $next($request);
            
            // Set response attributes
            $span->setAttributes([
                'http.status_code' => $response->getStatusCode(),
                'http.response_size' => strlen($response->getContent()),
            ]);
            
            // Determine span status based on HTTP status code
            $statusCode = $response->getStatusCode();
            if ($statusCode >= 400) {
                $span->setStatus(
                    $statusCode >= 500 ? StatusCode::STATUS_ERROR : StatusCode::STATUS_OK,
                    "HTTP {$statusCode}"
                );
            } else {
                $span->setStatus(StatusCode::STATUS_OK, 'Request completed successfully');
            }
            
            // Add event for request completion
            $span->addEvent('request.completed', [
                'http.status_code' => $statusCode,
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            // Record exception
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, 'Request failed with exception');
            
            throw $e;
        } finally {
            // Always detach scope and end span
            $scope->detach();
            $span->end();
        }
    }
}
