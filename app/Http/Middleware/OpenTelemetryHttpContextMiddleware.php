<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use OpenTelemetry\Context\Context;
use OpenTelemetry\API\Common\Instrumentation\Globals;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use Symfony\Component\HttpFoundation\Response;

class OpenTelemetryHttpContextMiddleware
{
    /**
     * Handle an incoming request to ensure proper context propagation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar se já existe um span ativo no contexto
        $currentContext = Context::getCurrent();
        $activeSpan = \OpenTelemetry\API\Trace\Span::fromContext($currentContext);
        
        // Se não há span ativo, criar um span raiz para HTTP
        if ($activeSpan->isRecording() === false) {
            $tracer = Globals::tracerProvider()->getTracer('laravel-http');
            $span = $tracer->spanBuilder('http.request')
                ->setSpanKind(SpanKind::KIND_SERVER)
                ->startSpan();
                
            $scope = $span->activate();
            
            try {
                // Set HTTP attributes
                $span->setAttributes([
                    'http.method' => $request->method(),
                    'http.url' => $request->fullUrl(),
                    'http.route' => $request->route() ? $request->route()->uri() : 'unknown',
                    'http.user_agent' => $request->userAgent(),
                    'service.name' => config('app.name', 'laravel'),
                    'http.layer' => 'server',
                ]);
                
                $span->addEvent('request.start');
                
                // Process the request
                $response = $next($request);
                
                // Set response attributes
                $span->setAttributes([
                    'http.status_code' => $response->getStatusCode(),
                    'http.response_size' => strlen($response->getContent()),
                ]);
                
                // Determine span status
                $statusCode = $response->getStatusCode();
                if ($statusCode >= 400) {
                    $span->setStatus(
                        $statusCode >= 500 ? StatusCode::STATUS_ERROR : StatusCode::STATUS_OK,
                        "HTTP {$statusCode}"
                    );
                } else {
                    $span->setStatus(StatusCode::STATUS_OK, 'Request completed successfully');
                }
                
                $span->addEvent('request.completed', [
                    'http.status_code' => $statusCode,
                ]);
                
                return $response;
                
            } catch (\Exception $e) {
                $span->recordException($e);
                $span->setStatus(StatusCode::STATUS_ERROR, 'Request failed');
                throw $e;
            } finally {
                $scope->detach();
                $span->end();
            }
        } else {
            // Se já há um span HTTP ativo, apenas continuar
            return $next($request);
        }
    }
}
