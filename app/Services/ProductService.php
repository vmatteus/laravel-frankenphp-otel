<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

class ProductService
{
    public function __construct(
        private ProductRepository $productRepository
    ) {}

    /**
     * Get all products with business logic
     */
    public function getAllProducts(): Collection
    {
        $tracer = Globals::tracerProvider()->getTracer('product-service');
        $span = $tracer->spanBuilder('ProductService.getAllProducts')
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();
        
        // Ativar o span no contexto
        $scope = $span->activate();
        
        try {
            Log::info('ProductService: Getting all products');
            
            $span->setAttributes([
                'service.method' => 'getAllProducts',
                'service.operation' => 'fetch_all_products',
                'service.layer' => 'business_logic',
            ]);
            
            $span->addEvent('business_logic_start');
            
            // Simula alguma lógica de negócio
            $products = $this->productRepository->all();
            
            $span->addEvent('repository_call_completed', [
                'products.retrieved' => $products->count()
            ]);
            
            // Simula processamento adicional
            sleep(0.05);
            
            $span->setAttributes([
                'products.count' => $products->count(),
                'processing.additional_delay' => '50ms',
            ]);
            
            Log::info('ProductService: Found ' . $products->count() . ' products');
            
            $span->setStatus(StatusCode::STATUS_OK, 'Products retrieved successfully');
            
            return $products;
            
        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, 'Failed to retrieve products');
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Get product by ID with validation
     */
    public function getProduct(int $id): ?Product
    {
        $tracer = Globals::tracerProvider()->getTracer('product-service');
        $span = $tracer->spanBuilder('ProductService.getProduct')
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();
            
        // Ativar o span no contexto
        $scope = $span->activate();
            
        try {
            Log::info("ProductService: Getting product with ID {$id}");
            
            $span->setAttributes([
                'service.method' => 'getProduct',
                'product.id' => $id,
                'validation.input_id' => $id,
                'service.layer' => 'business_logic',
            ]);
            
            // Span filho para validação
            $validationSpan = $tracer->spanBuilder('ProductService.validateProductId')
                ->setSpanKind(SpanKind::KIND_INTERNAL)
                ->startSpan();
                
            $validationScope = $validationSpan->activate();
                
            try {
                $validationSpan->setAttributes(['product.id' => $id]);
                
                if ($id <= 0) {
                    $validationSpan->addEvent('validation_failed', ['reason' => 'invalid_id']);
                    $validationSpan->setStatus(StatusCode::STATUS_ERROR, 'Invalid product ID');
                    Log::warning("ProductService: Invalid product ID {$id}");
                    return null;
                }
                
                $validationSpan->addEvent('validation_passed');
                $validationSpan->setStatus(StatusCode::STATUS_OK, 'ID validation passed');
                
            } finally {
                $validationScope->detach();
                $validationSpan->end();
            }
            
            $span->addEvent('validation_completed', ['valid' => true]);
            
            // Span filho para busca no repositório
            $repositorySpan = $tracer->spanBuilder('ProductService.fetchFromRepository')
                ->setSpanKind(SpanKind::KIND_INTERNAL)
                ->startSpan();
                
            $repositoryScope = $repositorySpan->activate();
                
            try {
                $repositorySpan->setAttributes([
                    'repository.operation' => 'find',
                    'product.id' => $id,
                ]);
                
                $product = $this->productRepository->find($id);
                
                if (!$product) {
                    $repositorySpan->addEvent('product_not_found');
                    $repositorySpan->setStatus(StatusCode::STATUS_OK, 'Product not found in database');
                    Log::warning("ProductService: Product with ID {$id} not found");
                } else {
                    $repositorySpan->addEvent('product_found', [
                        'product.name' => $product->name,
                        'product.price' => $product->price,
                    ]);
                    $repositorySpan->setStatus(StatusCode::STATUS_OK, 'Product found');
                }
                
                return $product;
                
            } finally {
                $repositoryScope->detach();
                $repositorySpan->end();
            }
            
        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, 'Failed to get product');
            throw $e;
        } finally {
            $span->setAttributes([
                'product.found' => isset($product) && $product !== null,
            ]);
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Create product with validation
     */
    public function createProduct(array $data): Product
    {
        Log::info('ProductService: Creating new product', ['name' => $data['name'] ?? 'unknown']);
        
        // Simula validação de negócio
        $this->validateProductData($data);
        
        // Simula cálculos ou processamento
        sleep(0.1);
        
        $product = $this->productRepository->create($data);
        
        Log::info("ProductService: Product created with ID {$product->id}");
        
        return $product;
    }

    /**
     * Update product
     */
    public function updateProduct(int $id, array $data): ?Product
    {
        Log::info("ProductService: Updating product {$id}");
        
        $product = $this->getProduct($id);
        
        if (!$product) {
            return null;
        }

        $this->validateProductData($data);
        
        // Simula processamento
        sleep(0.08);
        
        $this->productRepository->update($product, $data);
        
        Log::info("ProductService: Product {$id} updated successfully");
        
        return $product->fresh();
    }

    /**
     * Delete product
     */
    public function deleteProduct(int $id): bool
    {
        Log::info("ProductService: Deleting product {$id}");
        
        $product = $this->getProduct($id);
        
        if (!$product) {
            Log::warning("ProductService: Cannot delete non-existent product {$id}");
            return false;
        }

        $result = $this->productRepository->delete($product);
        
        if ($result) {
            Log::info("ProductService: Product {$id} deleted successfully");
        }
        
        return $result;
    }

    /**
     * Get active products only
     */
    public function getActiveProducts(): Collection
    {
        Log::info('ProductService: Getting active products only');
        
        $products = $this->productRepository->getActive();
        
        // Simula filtros adicionais de negócio
        sleep(0.03);
        
        return $products;
    }

    /**
     * Search products with business logic
     */
    public function searchProducts(string $query): Collection
    {
        $tracer = Globals::tracerProvider()->getTracer('product-service');
        $span = $tracer->spanBuilder('ProductService.searchProducts')
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();
            
        // Ativar o span no contexto
        $scope = $span->activate();
            
        try {
            Log::info("ProductService: Searching products with query: {$query}");
            
            $span->setAttributes([
                'service.method' => 'searchProducts',
                'search.query' => $query,
                'search.query_length' => strlen($query),
                'service.layer' => 'business_logic',
            ]);
            
            // Span filho para validação da query
            $validationSpan = $tracer->spanBuilder('ProductService.validateSearchQuery')
                ->setSpanKind(SpanKind::KIND_INTERNAL)
                ->startSpan();
                
            $validationScope = $validationSpan->activate();
                
            try {
                $validationSpan->setAttributes([
                    'search.query' => $query,
                    'search.min_length' => 2,
                ]);
                
                if (strlen($query) < 2) {
                    $validationSpan->addEvent('validation_failed', ['reason' => 'query_too_short']);
                    $validationSpan->setStatus(StatusCode::STATUS_ERROR, 'Search query too short');
                    Log::warning('ProductService: Search query too short');
                    return new Collection();
                }
                
                $validationSpan->addEvent('validation_passed');
                $validationSpan->setStatus(StatusCode::STATUS_OK, 'Search query validated');
                
            } finally {
                $validationScope->detach();
                $validationSpan->end();
            }
            
            $span->addEvent('search_processing_start');
            
            // Simula processamento da busca
            sleep(0.06);
            
            $span->addEvent('calling_repository_search');
            
            $products = $this->productRepository->searchByName($query);
            
            $span->addEvent('search_completed', [
                'results_count' => $products->count()
            ]);
            
            $span->setAttributes([
                'search.results_count' => $products->count(),
                'processing.delay' => '60ms',
            ]);
            
            Log::info("ProductService: Found {$products->count()} products for query: {$query}");
            
            $span->setStatus(StatusCode::STATUS_OK, 'Search completed successfully');
            
            return $products;
            
        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, 'Search failed');
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Private validation method
     */
    private function validateProductData(array $data): void
    {
        // Simula validação complexa
        sleep(0.02);
        
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Product name is required');
        }
        
        if (isset($data['price']) && $data['price'] < 0) {
            throw new \InvalidArgumentException('Product price cannot be negative');
        }
    }
}
