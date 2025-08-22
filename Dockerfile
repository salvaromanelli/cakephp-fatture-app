FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Install necessary extensions for CakePHP + MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Install additional PHP extensions that CakePHP may need
RUN docker-php-ext-install mbstring intl

# Enable mod_rewrite for clean URLs in Apache
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy the CakePHP project into the container
COPY ./app /var/www/html
WORKDIR /var/www/html

# Install composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html/tmp /var/www/html/logs

EXPOSE 80
CMD ["apache2-foreground"]