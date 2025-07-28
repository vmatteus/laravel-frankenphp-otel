# Use FrankenPHP with PHP 8.3
FROM dunglas/frankenphp:php8.3

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    build-essential \
    autoconf \
    pkg-config

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install OpenTelemetry PHP extension
RUN pecl install opentelemetry && docker-php-ext-enable opentelemetry

# Configure OpenTelemetry auto-instrumentation
RUN echo "auto_prepend_file=/app/bootstrap/otel_autoload.php" >> /usr/local/etc/php/conf.d/opentelemetry.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application code first
COPY . .

# Create necessary directories and set permissions before composer install
RUN mkdir -p /app/bootstrap/cache \
    && mkdir -p /app/storage/logs \
    && mkdir -p /app/storage/framework/cache \
    && mkdir -p /app/storage/framework/sessions \
    && mkdir -p /app/storage/framework/views \
    && chown -R www-data:www-data /app \
    && chmod -R 755 /app/storage \
    && chmod -R 755 /app/bootstrap/cache

# Copy composer files (might be redundant but ensures they're there)
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy package.json and package-lock.json if they exist  
COPY package*.json ./

# Install Node.js dependencies
RUN npm install

# Build assets
RUN npm run build

# Copy custom Caddyfile
COPY Caddyfile /etc/caddy/Caddyfile

# Copy and set up entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port 8000
EXPOSE 8000

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Start FrankenPHP
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
