<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

class ProductRepository
{
    /**
     * Get all products
     */
    public function all(): Collection
    {
        $tracer = Globals::tracerProvider()->getTracer('product-repository');
        $span = $tracer->spanBuilder('ProductRepository.all')
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();
            
        // Ativar o span no contexto
        $scope = $span->activate();
            
        try {
            $span->setAttributes([
                'repository.operation' => 'select_all',
                'database.table' => 'products',
                'repository.layer' => 'data_access',
            ]);
            
            $span->addEvent('database_query_start');
            sleep(0.1); // Simula consulta mais lenta para ver span
            
            $products = Product::all();
            
            $span->addEvent('database_query_completed', [
                'rows_returned' => $products->count()
            ]);
            
            $span->setAttributes([
                'database.rows_affected' => $products->count(),
            ]);
            
            $span->setStatus(StatusCode::STATUS_OK, 'Query executed successfully');
            
            return $products;
            
        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, 'Database query failed');
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Find product by ID
     */
    public function find(int $id): ?Product
    {
        $tracer = Globals::tracerProvider()->getTracer('product-repository');
        $span = $tracer->spanBuilder('ProductRepository.find')
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();
            
        // Ativar o span no contexto
        $scope = $span->activate();
            
        try {
            $span->setAttributes([
                'repository.operation' => 'find_by_id',
                'database.table' => 'products',
                'product.id' => $id,
                'repository.layer' => 'data_access',
            ]);
            
            $span->addEvent('database_query_start', ['query_type' => 'find_by_id']);
            sleep(0.05); // Simula consulta
            
            $product = Product::find($id);
            
            if ($product) {
                $span->addEvent('product_found', [
                    'product.name' => $product->name,
                    'product.price' => $product->price,
                ]);
                $span->setAttributes([
                    'product.found' => true,
                    'product.name' => $product->name,
                ]);
            } else {
                $span->addEvent('product_not_found');
                $span->setAttributes(['product.found' => false]);
            }
            
            $span->setStatus(StatusCode::STATUS_OK, 'Find query completed');
            
            return $product;
            
        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, 'Find query failed');
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Create a new product
     */
    public function create(array $data): Product
    {
        sleep(0.2); // Simula operação de escrita
        return Product::create($data);
    }

    /**
     * Update product
     */
    public function update(Product $product, array $data): bool
    {
        sleep(0.15); // Simula operação de update
        return $product->update($data);
    }

    /**
     * Delete product
     */
    public function delete(Product $product): bool
    {
        sleep(0.1); // Simula operação de delete
        return $product->delete();
    }

    /**
     * Get active products
     */
    public function getActive(): Collection
    {
        sleep(0.08); // Simula consulta com filtro
        return Product::where('active', true)->get();
    }

    /**
     * Search products by name
     */
    public function searchByName(string $name): Collection
    {
        $tracer = Globals::tracerProvider()->getTracer('product-repository');
        $span = $tracer->spanBuilder('ProductRepository.searchByName')
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();
            
        try {
            $span->setAttributes([
                'repository.operation' => 'search_by_name',
                'database.table' => 'products',
                'search.query' => $name,
                'search.query_length' => strlen($name),
            ]);
            
            $span->addEvent('database_search_start', ['pattern' => "%{$name}%"]);
            sleep(0.12); // Simula busca
            
            $products = Product::where('name', 'like', "%{$name}%")->get();
            
            $span->addEvent('database_search_completed', [
                'results_found' => $products->count()
            ]);
            
            $span->setAttributes([
                'search.results_count' => $products->count(),
                'database.rows_affected' => $products->count(),
            ]);
            
            $span->setStatus(StatusCode::STATUS_OK, 'Search query completed');
            
            return $products;
            
        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, 'Search query failed');
            throw $e;
        } finally {
            $span->end();
        }
    }
}
