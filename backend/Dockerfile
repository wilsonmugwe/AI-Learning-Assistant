# Use official PHP image with necessary extensions
FROM php:8.2-fpm

# Set working directory inside container
WORKDIR /var/www

# Copy Laravel backend files into container
COPY . /var/www

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev

# Generate Laravel app key
RUN php artisan key:generate

# Expose port (if using Laravel's PHP dev server for simplicity)
EXPOSE 8000

# Start Laravel using built-in PHP server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
