# Laravel 12 with FrankenPHP and Docker

A modern Laravel 12 development environment using FrankenPHP, Docker, and OpenTelemetry.

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
