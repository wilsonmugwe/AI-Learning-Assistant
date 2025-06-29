# 1. Base image
FROM php:8.2-fpm

# 2. Working directory
WORKDIR /var/www

# 3. Install dependencies
RUN apt-get update && apt-get install -y \
    git unzip curl libpng-dev libonig-dev libxml2-dev libpq-dev zip \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd

# 4. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Copy application source
COPY . .

# 6. Set permissions for storage and cache
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# 7. Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# 8. Expose port 8080 for Laravel's built-in server
EXPOSE 8080

# 9. Start Laravel with artisan on port 8080
CMD php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache \
 && php artisan migrate --force \
 && php artisan serve --host=0.0.0.0 --port=8080
