# 🚀 FrankenPHP Laravel OpenTelemetry Implementation

Uma implementação completa de telemetria hierárquica usando OpenTelemetry em uma aplicação Laravel rodando no FrankenPHP, com instrumentação manual em todas as camadas da aplicação.

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Arquitetura](#arquitetura)
- [Configuração do Ambiente](#configuração-do-ambiente)
- [Implementação Técnica](#implementação-técnica)
- [Estrutura de Telemetria](#estrutura-de-telemetria)
- [Configurações FrankenPHP](#configurações-frankenphp)
- [Troubleshooting](#troubleshooting)
- [Exemplos de Uso](#exemplos-de-uso)

## 🎯 Visão Geral

Este projeto demonstra como implementar telemetria hierárquica completa em uma aplicação Laravel usando:

- **FrankenPHP** como servidor web
- **OpenTelemetry PHP Extension** para instrumentação nativa
- **OpenTelemetry SDK** para telemetria customizada
- **Context Propagation** para spans aninhados
- **Auto-instrumentation Symfony** conectada com spans manuais

### 🏆 Resultado Alcançado

```
HTTP Request (Auto-Instrumentation)
├── ProductController.index [http]
│   └── ProductService.getAllProducts [business_logic]
│       └── ProductRepository.all [data_access]
│
├── ProductController.show [http]
│   └── ProductService.getProduct [business_logic]
│       ├── ProductService.validateProductId
│       └── ProductService.fetchFromRepository
│           └── ProductRepository.find [data_access]
│
└── ProductController.search [http]
    └── ProductService.searchProducts [business_logic]
        ├── ProductService.validateSearchQuery
        └── ProductRepository.searchByName [data_access]
```

## 🏗️ Arquitetura

### Stack Tecnológica

- **Runtime:** FrankenPHP (Go + PHP-FPM)
- **Framework:** Laravel 11
- **Database:** MySQL 8.0
- **Telemetry:** OpenTelemetry PHP Extension 1.2.0
- **Observability:** OTLP Exporter (compatível com Jaeger, Zipkin, APM)

### Camadas da Aplicação

1. **HTTP Layer** - Controllers com spans de requisição
2. **Business Logic Layer** - Services com lógica de negócio
3. **Data Access Layer** - Repositories com operações de banco

## ⚙️ Configuração do Ambiente

### Pré-requisitos

```bash
- Docker & Docker Compose
- Git
- Coletor OpenTelemetry (Jaeger, Zipkin, ou APM)
```

### Instalação

```bash
# Clone o repositório
git clone <repository-url>
cd frankenphp-test-otel

# Configure o coletor OpenTelemetry
export OTEL_EXPORTER_OTLP_ENDPOINT="http://host.docker.internal:4318"
export OTEL_EXPORTER_OTLP_TRACES_ENDPOINT="http://host.docker.internal:4318/v1/traces"

# Inicie a aplicação
docker compose up --build -d

# Execute as migrações e seeders
docker compose exec app php artisan migrate:fresh --seed
```

### Estrutura do Projeto

```
app/
├── Http/Controllers/
│   └── ProductController.php     # HTTP Layer com OpenTelemetry
├── Services/
│   └── ProductService.php        # Business Logic Layer
├── Repositories/
│   └── ProductRepository.php     # Data Access Layer
├── Models/
│   └── Product.php              # Eloquent Model
└── Providers/
    └── OpenTelemetryServiceProvider.php

bootstrap/
├── otel.php                     # OpenTelemetry SDK Setup
└── otel_autoload.php           # Auto-instrumentation Loader

docker-compose.yml               # Container orchestration
Dockerfile                       # FrankenPHP + OpenTelemetry
```

## 🔧 Implementação Técnica

### 1. Configuração do Container

#### Dockerfile - Extensão OpenTelemetry

```dockerfile
# Install OpenTelemetry PHP extension
RUN pecl install opentelemetry && docker-php-ext-enable opentelemetry

# Configure OpenTelemetry auto-instrumentation
RUN echo "auto_prepend_file=/app/bootstrap/otel_autoload.php" >> /usr/local/etc/php/conf.d/opentelemetry.ini
RUN echo "opentelemetry.enable_auto_instrumentation=1" >> /usr/local/etc/php/conf.d/opentelemetry.ini
RUN echo "opentelemetry.context_storage=fiber" >> /usr/local/etc/php/conf.d/opentelemetry.ini
```

#### Docker Compose - Variáveis de Ambiente

```yaml
environment:
  # OpenTelemetry Core Configuration
  - OTEL_SERVICE_NAME=frankenphp-laravel
  - OTEL_SERVICE_VERSION=1.0.0
  - OTEL_ENVIRONMENT=local
  - OTEL_EXPORTER_OTLP_ENDPOINT=${OTEL_EXPORTER_OTLP_ENDPOINT:-}
  - OTEL_EXPORTER_OTLP_TRACES_ENDPOINT=${OTEL_EXPORTER_OTLP_TRACES_ENDPOINT:-}
  
  # OpenTelemetry PHP Specific
  - OTEL_PHP_AUTOLOAD_ENABLED=true
  - OTEL_PROPAGATORS=tracecontext,baggage
  - OTEL_TRACES_SAMPLER=always_on
  - OTEL_BSP_SCHEDULE_DELAY=5000
  - OTEL_BSP_EXPORT_TIMEOUT=30000
  - OTEL_BSP_MAX_EXPORT_BATCH_SIZE=512
  - OTEL_BSP_MAX_QUEUE_SIZE=2048
  - OTEL_PHP_DISABLED_INSTRUMENTATIONS=""
  - OTEL_PHP_TRACES_PROCESSOR=batch
```

### 2. Context Propagation Pattern

#### Padrão Implementado em Todas as Camadas

```php
<?php

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

class ExampleService
{
    public function methodWithTelemetry(): mixed
    {
        $tracer = Globals::tracerProvider()->getTracer('service-name');
        $span = $tracer->spanBuilder('Service.method')
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();
        
        // 🔑 CHAVE: Ativar o span no contexto atual
        $scope = $span->activate();
        
        try {
            // Configurar atributos
            $span->setAttributes([
                'service.method' => 'methodName',
                'service.layer' => 'business_logic',
                'custom.attribute' => 'value',
            ]);
            
            // Adicionar eventos
            $span->addEvent('operation_start');
            
            // Executar operação
            $result = $this->performOperation();
            
            // Atributos de resultado
            $span->setAttributes(['operation.result' => 'success']);
            $span->setStatus(StatusCode::STATUS_OK, 'Operation completed');
            
            return $result;
            
        } catch (\Exception $e) {
            // Registrar exceção
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, 'Operation failed');
            throw $e;
            
        } finally {
            // 🔑 ESSENCIAL: Sempre desativar o contexto e finalizar span
            $scope->detach();
            $span->end();
        }
    }
}
```

### 3. Instrumentação por Camada

#### HTTP Layer - Controllers

```php
class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tracer = Globals::tracerProvider()->getTracer('product-controller');
        $span = $tracer->spanBuilder('ProductController.index')
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();
        
        $scope = $span->activate();
        
        try {
            $span->setAttributes([
                'controller.method' => 'index',
                'controller.action' => 'list_all_products',
                'http.method' => $request->method(),
                'http.url' => $request->fullUrl(),
                'controller.layer' => 'http',
            ]);
            
            $span->addEvent('controller_start');
            
            // Chamada para service layer (context propagado automaticamente)
            $products = $this->productService->getAllProducts();
            
            $span->addEvent('service_call_completed', [
                'products_retrieved' => $products->count()
            ]);
            
            $span->setStatus(StatusCode::STATUS_OK, 'Products listed successfully');
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'total' => $products->count(),
            ]);
            
        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, 'Failed to list products');
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}
```

#### Business Logic Layer - Services

```php
class ProductService
{
    public function getAllProducts(): Collection
    {
        $tracer = Globals::tracerProvider()->getTracer('product-service');
        $span = $tracer->spanBuilder('ProductService.getAllProducts')
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();
        
        $scope = $span->activate();
        
        try {
            $span->setAttributes([
                'service.method' => 'getAllProducts',
                'service.operation' => 'fetch_all_products',
                'service.layer' => 'business_logic',
            ]);
            
            $span->addEvent('business_logic_start');
            
            // Chamada para repository (context propagado)
            $products = $this->productRepository->all();
            
            $span->addEvent('repository_call_completed', [
                'products.retrieved' => $products->count()
            ]);
            
            // Simular processamento adicional
            sleep(0.05);
            
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
}
```

#### Data Access Layer - Repositories

```php
class ProductRepository
{
    public function all(): Collection
    {
        $tracer = Globals::tracerProvider()->getTracer('product-repository');
        $span = $tracer->spanBuilder('ProductRepository.all')
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();
        
        $scope = $span->activate();
        
        try {
            $span->setAttributes([
                'repository.operation' => 'select_all',
                'database.table' => 'products',
                'repository.layer' => 'data_access',
            ]);
            
            $span->addEvent('database_query_start');
            
            // Simular consulta
            sleep(0.1);
            
            $products = Product::all();
            
            $span->addEvent('database_query_completed', [
                'rows_returned' => $products->count()
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
}
```

## 📊 Estrutura de Telemetria

### Atributos Padronizados

#### HTTP Layer
```php
'controller.layer' => 'http',
'controller.method' => 'index',
'controller.action' => 'list_all_products',
'http.method' => 'GET',
'http.url' => 'http://localhost:8000/api/products',
'response.type' => 'json',
```

#### Business Logic Layer
```php
'service.layer' => 'business_logic',
'service.method' => 'getAllProducts',
'service.operation' => 'fetch_all_products',
'products.count' => 3,
'processing.additional_delay' => '50ms',
```

#### Data Access Layer
```php
'repository.layer' => 'data_access',
'repository.operation' => 'select_all',
'database.table' => 'products',
'database.rows_affected' => 3,
```

### Events Estruturados

```php
// Controller Events
$span->addEvent('controller_start');
$span->addEvent('service_call_completed', ['products_retrieved' => 3]);

// Service Events
$span->addEvent('business_logic_start');
$span->addEvent('repository_call_completed', ['products.retrieved' => 3]);

// Repository Events
$span->addEvent('database_query_start');
$span->addEvent('database_query_completed', ['rows_returned' => 3]);
```

### Status e Error Handling

```php
// Sucesso
$span->setStatus(StatusCode::STATUS_OK, 'Operation completed successfully');

// Erro
$span->setStatus(StatusCode::STATUS_ERROR, 'Operation failed');
$span->recordException($exception);

// Validation
$span->addEvent('validation_failed', ['errors' => $validationErrors]);
```

## 🔧 Configurações FrankenPHP

### Configurações Críticas para Context Propagation

#### PHP.ini Extensions
```ini
extension=opentelemetry.so
auto_prepend_file=/app/bootstrap/otel_autoload.php
opentelemetry.enable_auto_instrumentation=1
opentelemetry.context_storage=fiber  # CRUCIAL para FrankenPHP
```

#### Bootstrap Auto-loader
```php
<?php
// bootstrap/otel_autoload.php

if (extension_loaded('opentelemetry')) {
    // Load Composer autoloader
    if (!class_exists('OpenTelemetry\SDK\Sdk')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }
    
    // Include Symfony auto-instrumentation
    $registerFile = __DIR__ . '/../vendor/open-telemetry/opentelemetry-auto-symfony/_register.php';
    if (file_exists($registerFile)) {
        require_once $registerFile;
    }
    
    // Force context storage initialization for fiber-safe operation
    if (class_exists('OpenTelemetry\Context\Context')) {
        \OpenTelemetry\Context\Context::storage();
    }
}
```

### SDK Configuration

```php
<?php
// bootstrap/otel.php

use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\Contrib\Otlp\SpanExporter;

// Create resource info
$resource = ResourceInfoFactory::defaultResource()->merge(
    ResourceInfo::create(Attributes::create([
        ResourceAttributes::SERVICE_NAME => env('OTEL_SERVICE_NAME', 'frankenphp-laravel'),
        ResourceAttributes::SERVICE_VERSION => env('OTEL_SERVICE_VERSION', '1.0.0'),
        ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => env('OTEL_ENVIRONMENT', 'local'),
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
```

## 🛠️ Troubleshooting

### Problemas Comuns

#### 1. Spans Desconectados (HTTP separado da aplicação)

**Sintoma:** Spans aparecem separados no dashboard
**Causa:** Context não está sendo propagado entre layers
**Solução:**
```php
// Sempre usar span activation
$scope = $span->activate();
try {
    // operação
} finally {
    $scope->detach();  // ESSENCIAL
    $span->end();
}
```

#### 2. FrankenPHP Context Issues

**Sintoma:** Spans não aparecem aninhados
**Causa:** Context storage não configurado para fiber
**Solução:**
```ini
opentelemetry.context_storage=fiber
```

#### 3. Auto-instrumentation Não Funciona

**Sintoma:** Nenhum span automático aparece
**Causa:** Auto-prepend não configurado
**Solução:**
```ini
auto_prepend_file=/app/bootstrap/otel_autoload.php
```

#### 4. Performance Issues

**Sintoma:** Aplicação lenta
**Causa:** Span processor em modo sync
**Solução:**
```yaml
OTEL_PHP_TRACES_PROCESSOR=batch
OTEL_BSP_SCHEDULE_DELAY=5000
```

### Debug Commands

```bash
# Verificar se extensão está carregada
docker compose exec app php -m | grep opentelemetry

# Verificar configuração PHP
docker compose exec app php -i | grep opentelemetry

# Testar exportação de spans
docker compose exec app php -r "var_dump(extension_loaded('opentelemetry'));"

# Ver logs do FrankenPHP
docker compose logs app --tail=50
```

## 🧪 Exemplos de Uso

### Endpoints Disponíveis

```bash
# Listar produtos (com spans hierárquicos)
curl "http://localhost:8000/api/products"

# Obter produto específico (com validação aninhada)
curl "http://localhost:8000/api/products/1"

# Buscar produtos (com validação de query)
curl "http://localhost:8000/api/products/search?q=smartphone"

# Criar produto
curl -X POST "http://localhost:8000/api/products" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Product","price":99.99,"stock":10}'
```

### Testando Telemetria

```bash
# Gerar carga para ver spans
for i in {1..10}; do
  curl -s "http://localhost:8000/api/products" > /dev/null
  curl -s "http://localhost:8000/api/products/$((i % 3 + 1))" > /dev/null
done
```

### Estrutura Esperada no Dashboard

```
🌐 HTTP GET /api/products (200ms)
├── 🎮 ProductController.index (180ms)
│   └── 🔧 ProductService.getAllProducts (150ms)
│       └── 💾 ProductRepository.all (100ms)
│
🌐 HTTP GET /api/products/1 (150ms)
├── 🎮 ProductController.show (130ms)
│   └── 🔧 ProductService.getProduct (100ms)
│       ├── ✅ ProductService.validateProductId (5ms)
│       └── 🔧 ProductService.fetchFromRepository (90ms)
│           └── 💾 ProductRepository.find (50ms)
```

## 📈 Métricas e Observabilidade

### Atributos de Performance

- **Duration por Layer:** Tempo gasto em cada camada
- **Database Queries:** Número e duração de consultas
- **Error Rate:** Taxa de erros por endpoint
- **Throughput:** Requisições por segundo

### Dashboards Sugeridos

1. **Request Overview:** Volume, latência, errors
2. **Service Map:** Dependências entre serviços
3. **Database Performance:** Query performance e N+1
4. **Error Analysis:** Stack traces e frequency

## 🚀 Deploy e Produção

### Configurações de Produção

```yaml
# docker-compose.prod.yml
environment:
  - OTEL_TRACES_SAMPLER=traceidratio
  - OTEL_TRACES_SAMPLER_ARG=0.1  # 10% sampling
  - OTEL_BSP_SCHEDULE_DELAY=10000
  - OTEL_BSP_MAX_EXPORT_BATCH_SIZE=1024
```

### Otimizações

1. **Sampling:** Reduzir volume em produção
2. **Batch Processing:** Configurar buffers adequados
3. **Resource Attributes:** Adicionar metadados de deploy
4. **Error Filtering:** Filtrar erros conhecidos

---

**🎉 Esta implementação demonstra telemetria hierárquica completa em FrankenPHP com observabilidade de classe enterprise!**

## 🚀 Features

- **Laravel 12** - Latest version of Laravel
- **FrankenPHP** - Modern PHP application server
- **Docker Compose** - Easy development environment
- **MySQL 8.0** - Database
- **Redis** - Caching and sessions
- **MailHog** - Email testing
- **OpenTelemetry** - Observability and tracing

## 📋 Prerequisites

- Docker and Docker Compose
- Git

## 🛠️ Quick Start

1. **Clone and setup:**
   ```bash
   git clone <your-repo> frankenphp-test-otel
   cd frankenphp-test-otel
   ```

2. **Start the development environment:**
   ```bash
   ./dev.sh start
   ```

3. **Run migrations:**
   ```bash
   ./dev.sh migrate
   ```

4. **Access the application:**
   - **Laravel App**: http://localhost:8000
   - **MailHog**: http://localhost:8025
   - **MySQL**: localhost:3306
   - **Redis**: localhost:6379

## 🔧 Development Commands

The `dev.sh` script provides convenient commands for development:

```bash
./dev.sh start      # Start all containers
./dev.sh stop       # Stop all containers
./dev.sh restart    # Restart all containers
./dev.sh build      # Build containers
./dev.sh logs       # View container logs
./dev.sh shell      # Open bash shell in app container
./dev.sh artisan    # Run artisan commands
./dev.sh composer   # Run composer commands
./dev.sh npm        # Run npm commands
./dev.sh migrate    # Run database migrations
./dev.sh seed       # Run database seeders
./dev.sh fresh      # Fresh migration with seed
./dev.sh test       # Run tests
```

### Examples:

```bash
# Run artisan commands
./dev.sh artisan make:controller UserController
./dev.sh artisan make:model Product -m

# Install composer packages
./dev.sh composer require spatie/laravel-permission

# Install npm packages
./dev.sh npm install axios
./dev.sh npm run dev

# Run tests
./dev.sh test
```

## 🐳 Docker Services

- **app** - Laravel application with FrankenPHP
- **mysql** - MySQL 8.0 database
- **redis** - Redis for caching and sessions
- **mailhog** - Email testing tool

## 🔧 Configuration

### Environment Variables

The `.env` file is configured for Docker development:

- Database: MySQL (mysql:3306)
- Cache: Redis
- Mail: MailHog
- Queue: Database

### Database Connection

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=password
```

### Redis Connection

```env
REDIS_HOST=redis
REDIS_PORT=6379
```

### Mail Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
```

## 📁 Project Structure

```
frankenphp-test-otel/
├── app/                    # Laravel application code
├── config/                 # Configuration files
├── database/               # Migrations, seeders, factories
├── public/                 # Public web files
├── resources/              # Views, assets, lang files
├── routes/                 # Route definitions
├── storage/                # Logs, cache, uploads
├── tests/                  # Test files
├── docker-compose.yml      # Docker services configuration
├── Dockerfile              # Application container
├── Caddyfile              # FrankenPHP configuration
├── dev.sh                 # Development helper script
└── README.md              # This file
```

## 🧪 Testing

Run the test suite:

```bash
./dev.sh test
```

## 📚 Learn More

- [Laravel Documentation](https://laravel.com/docs)
- [FrankenPHP Documentation](https://frankenphp.dev/)
- [Docker Documentation](https://docs.docker.com/)

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
