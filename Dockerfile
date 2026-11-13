# ============================================================
# AeroGlide Travel Platform — Production-Ready Dockerfile
# Base: PHP 8.3 Apache + PDO MySQL + Internationalization + OpCache
# ============================================================

FROM php:8.3-apache

# 1. Install OS libraries and PHP build dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    libzip-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-install -j$(nproc) \
    intl \
    pdo \
    pdo_mysql \
    opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 2. Install Composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 3. Enable essential Apache modules
RUN a2enmod rewrite headers

# 4. Configure Apache DocumentRoot to permit .htaccess rewrites
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/conf-available/*.conf

# 5. Set tailored PHP production/development settings
RUN echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/aeroglide.ini \
    && echo "upload_max_filesize = 64M" >> /usr/local/etc/php/conf.d/aeroglide.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/aeroglide.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/aeroglide.ini \
    && echo "date.timezone = Asia/Manila" >> /usr/local/etc/php/conf.d/aeroglide.ini \
    && echo "variables_order = 'EGPCS'" >> /usr/local/etc/php/conf.d/aeroglide.ini

# 6. Set working directory
WORKDIR /var/www/html

# 7. Copy application source files
COPY . /var/www/html/

# 8. Configure and normalize entrypoint script line endings (Windows/Unix safe)
COPY docker/entrypoint.sh /usr/local/bin/aeroglide-entrypoint
RUN chmod +x /usr/local/bin/aeroglide-entrypoint \
    && sed -i 's/\r$//' /usr/local/bin/aeroglide-entrypoint

# 9. Create cache/log directory and grant permissions for Apache
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/aeroglide-entrypoint"]
CMD ["apache2-foreground"]
