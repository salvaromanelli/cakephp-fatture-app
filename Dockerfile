FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/webroot

# Arreglar repositorios y limpiar cache
RUN rm -rf /var/lib/apt/lists/* && \
    apt-get clean && \
    apt-get update --fix-missing

# Instalar dependencias básicas (sin supervisor y cron que no necesitas)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libicu-dev libzip-dev libonig-dev \
    default-mysql-client \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) pdo_mysql mbstring gd intl zip opcache

# Configurar Apache
RUN a2enmod rewrite headers && \
    sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar aplicación
COPY app/ ./

# Instalar dependencias de Composer si existe composer.json
RUN if [ -f composer.json ]; then \
    composer install --no-dev --optimize-autoloader --no-interaction || true; \
    fi

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    mkdir -p tmp logs && \
    chmod -R 777 tmp logs

# Health check
RUN echo '<?php http_response_code(200); echo "healthy"; ?>' > webroot/health.php

EXPOSE 80
CMD ["apache2-foreground"]