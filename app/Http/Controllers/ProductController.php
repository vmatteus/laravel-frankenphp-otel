<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use OpenTelemetry\API\Globals;
use OpenTelemetry\Context\Context;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $tracer = Globals::tracerProvider()->getTracer('product-controller');
        $span = $tracer->spanBuilder('ProductController.index')
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();
        
        // Ativar o span no contexto atual
        $scope = $span->activate();
        
        try {
            Log::info('ProductController: Listing all products');
            
            $span->setAttributes([
                'controller.method' => 'index',
                'controller.action' => 'list_all_products',
                'http.method' => $request->method(),
                'http.url' => $request->fullUrl(),
                'controller.layer' => 'http',
            ]);
            
            $span->addEvent('controller_start');
            
            $products = $this->productService->getAllProducts();
            
            $span->addEvent('service_call_completed', [
                'products_retrieved' => $products->count()
            ]);
            
            $span->setAttributes([
                'products.count' => $products->count(),
                'response.type' => 'json',
            ]);
            
            $span->addEvent('products_retrieved', ['count' => $products->count()]);
            $span->setStatus(StatusCode::STATUS_OK, 'Products listed successfully');
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'total' => $products->count(),
            ]);
            
        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, 'Failed to list products');
            Log::error('ProductController: Error listing products', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving products',
            ], 500);
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $tracer = Globals::tracerProvider()->getTracer('product-controller');
        $span = $tracer->spanBuilder('ProductController.store')->startSpan();
        
        try {
            Log::info('ProductController: Creating new product');
            
            $span->setAttributes([
                'controller.method' => 'store',
                'http.method' => $request->method(),
            ]);
            
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'active' => 'boolean',
            ]);
            
            $span->addEvent('validation_passed');
            
            $product = $this->productService->createProduct($data);
            
            $span->setAttributes([
                'product.id' => $product->id,
                'product.name' => $product->name,
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Product created successfully',
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            $span->setAttributes(['validation.failed' => true]);
            $span->addEvent('validation_failed', ['errors' => $e->errors()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            $span->recordException($e);
            Log::error('ProductController: Error creating product', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating product',
            ], 500);
        } finally {
            $span->end();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $tracer = Globals::tracerProvider()->getTracer('product-controller');
        $span = $tracer->spanBuilder('ProductController.show')
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();
        
        // Ativar o span no contexto atual
        $scope = $span->activate();
        
        try {
            Log::info("ProductController: Showing product {$id}");
            
            $span->setAttributes([
                'controller.method' => 'show',
                'controller.action' => 'get_single_product',
                'product.id' => $id,
                'controller.layer' => 'http',
            ]);
            
            $span->addEvent('controller_start');
            
            $product = $this->productService->getProduct((int) $id);
            
            if (!$product) {
                $span->addEvent('product_not_found');
                $span->setStatus(StatusCode::STATUS_OK, 'Product not found');
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }
            
            $span->addEvent('service_call_completed', [
                'product_found' => true,
                'product_name' => $product->name
            ]);
            
            $span->setAttributes([
                'product.name' => $product->name,
                'product.found' => true,
                'response.type' => 'json',
            ]);
            
            $span->setStatus(StatusCode::STATUS_OK, 'Product retrieved successfully');
            
            return response()->json([
                'success' => true,
                'data' => $product,
            ]);
            
        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, 'Failed to retrieve product');
            Log::error("ProductController: Error showing product {$id}", ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product',
            ], 500);
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tracer = Globals::tracerProvider()->getTracer('product-controller');
        $span = $tracer->spanBuilder('ProductController.update')->startSpan();
        
        try {
            Log::info("ProductController: Updating product {$id}");
            
            $span->setAttributes([
                'controller.method' => 'update',
                'product.id' => $id,
                'http.method' => $request->method(),
            ]);
            
            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric|min:0',
                'stock' => 'sometimes|required|integer|min:0',
                'active' => 'boolean',
            ]);
            
            $span->addEvent('validation_passed');
            
            $product = $this->productService->updateProduct((int) $id, $data);
            
            if (!$product) {
                $span->addEvent('product_not_found');
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }
            
            $span->setAttributes([
                'product.name' => $product->name,
                'product.updated' => true,
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Product updated successfully',
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            $span->setAttributes(['validation.failed' => true]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            $span->recordException($e);
            Log::error("ProductController: Error updating product {$id}", ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error updating product',
            ], 500);
        } finally {
            $span->end();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $tracer = Globals::tracerProvider()->getTracer('product-controller');
        $span = $tracer->spanBuilder('ProductController.destroy')->startSpan();
        
        try {
            Log::info("ProductController: Deleting product {$id}");
            
            $span->setAttributes([
                'controller.method' => 'destroy',
                'product.id' => $id,
            ]);
            
            $deleted = $this->productService->deleteProduct((int) $id);
            
            if (!$deleted) {
                $span->addEvent('product_not_found_or_delete_failed');
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found or could not be deleted',
                ], 404);
            }
            
            $span->setAttributes(['product.deleted' => true]);
            
            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            $span->recordException($e);
            Log::error("ProductController: Error deleting product {$id}", ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting product',
            ], 500);
        } finally {
            $span->end();
        }
    }

    /**
     * Search products
     */
    public function search(Request $request): JsonResponse
    {
        $tracer = Globals::tracerProvider()->getTracer('product-controller');
        $span = $tracer->spanBuilder('ProductController.search')->startSpan();
        
        try {
            $query = $request->get('q', '');
            
            Log::info("ProductController: Searching products with query: {$query}");
            
            $span->setAttributes([
                'controller.method' => 'search',
                'search.query' => $query,
                'search.query_length' => strlen($query),
            ]);
            
            $products = $this->productService->searchProducts($query);
            
            $span->setAttributes([
                'search.results_count' => $products->count(),
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'query' => $query,
                'total' => $products->count(),
            ]);
            
        } catch (\Exception $e) {
            $span->recordException($e);
            Log::error('ProductController: Error searching products', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error searching products',
            ], 500);
        } finally {
            $span->end();
        }
    }
}
