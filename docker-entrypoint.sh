#!/bin/bash
set -e

# Check if vendor directory exists and has content
if [ ! -d "/app/vendor" ] || [ -z "$(ls -A /app/vendor)" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader
fi

# Check if node_modules directory exists and has content
if [ ! -d "/app/node_modules" ] || [ -z "$(ls -A /app/node_modules)" ]; then
    echo "Installing Node.js dependencies..."
    npm install
    echo "Building assets..."
    npm run build
fi

# Ensure proper permissions
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 755 /app/storage /app/bootstrap/cache

# Execute the original command
exec "$@"
