#!/bin/bash

# Laravel Docker Development Script

echo "🚀 Laravel 12 with FrankenPHP Development Environment"
echo "======================================================="

case "$1" in
    "start"|"up")
        echo "📦 Starting containers..."
        docker-compose up -d
        echo "✅ Containers started!"
        echo "🌐 Application: http://localhost:8000"
        echo "📧 MailHog: http://localhost:8025"
        echo "🗄️  MySQL: localhost:3306"
        echo "📊 Redis: localhost:6380"
        ;;
    "stop"|"down")
        echo "🛑 Stopping containers..."
        docker-compose down
        echo "✅ Containers stopped!"
        ;;
    "restart")
        echo "🔄 Restarting containers..."
        docker-compose down
        docker-compose up -d
        echo "✅ Containers restarted!"
        ;;
    "build")
        echo "🔨 Building containers..."
        docker-compose build --no-cache
        echo "✅ Build complete!"
        ;;
    "logs")
        echo "📋 Viewing logs..."
        docker-compose logs -f
        ;;
    "shell"|"bash")
        echo "🐚 Opening shell in app container..."
        docker-compose exec app bash
        ;;
    "artisan")
        shift
        echo "🎨 Running artisan command: $@"
        docker-compose exec app php artisan "$@"
        ;;
    "composer")
        shift
        echo "🎼 Running composer command: $@"
        docker-compose exec app composer "$@"
        ;;
    "npm")
        shift
        echo "📦 Running npm command: $@"
        docker-compose exec app npm "$@"
        ;;
    "migrate")
        echo "🗄️  Running migrations..."
        docker-compose exec app php artisan migrate
        ;;
    "seed")
        echo "🌱 Running seeders..."
        docker-compose exec app php artisan db:seed
        ;;
    "fresh")
        echo "🔄 Fresh migration with seed..."
        docker-compose exec app php artisan migrate:fresh --seed
        ;;
    "test")
        echo "🧪 Running tests..."
        docker-compose exec app php artisan test
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|build|logs|shell|artisan|composer|npm|migrate|seed|fresh|test}"
        echo ""
        echo "Commands:"
        echo "  start     - Start all containers"
        echo "  stop      - Stop all containers"
        echo "  restart   - Restart all containers"
        echo "  build     - Build containers"
        echo "  logs      - View container logs"
        echo "  shell     - Open bash shell in app container"
        echo "  artisan   - Run artisan commands"
        echo "  composer  - Run composer commands"
        echo "  npm       - Run npm commands"
        echo "  migrate   - Run database migrations"
        echo "  seed      - Run database seeders"
        echo "  fresh     - Fresh migration with seed"
        echo "  test      - Run tests"
        exit 1
        ;;
esac
