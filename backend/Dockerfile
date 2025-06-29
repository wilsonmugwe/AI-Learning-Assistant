# 1. Use base PHP image
FROM php:8.2-fpm

# 2. Set working directory
WORKDIR /var/www

# 3. Install required system packages
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# 4. Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 5. Copy Laravel files
COPY . .

# 6. Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

# 7. Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# 8. Generate Laravel key
RUN cp .env.example .env && php artisan key:generate

# 9. Expose port and launch server
EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
