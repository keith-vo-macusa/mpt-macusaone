########################################
# Stage 1: Build vendor
########################################
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist



########################################
# Stage 2: Runtime
########################################
FROM php:8.3-fpm-alpine

# Install packages
RUN apk add --no-cache \
    nginx \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    bash

# Install PHP extensions
RUN docker-php-ext-install \
    intl \
    pdo \
    pdo_mysql \
    zip

# Set working directory
WORKDIR /var/www/html

# Copy source
COPY . /var/www/html

# Copy vendor
COPY --from=vendor /app/vendor /var/www/html/vendor

# Optimize autoload
RUN composer dump-autoload \
    --no-dev \
    --optimize

# Create Laravel dirs
RUN mkdir -p storage/logs bootstrap/cache

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

########################################
# 🔥 Setup nginx inline (KHÔNG cần file ngoài)
########################################
RUN rm /etc/nginx/nginx.conf

RUN echo 'events {} \
http { \
    server { \
        listen 80; \
        root /var/www/html/public; \
        index index.php index.html; \
        \
        location / { \
            try_files $uri $uri/ /index.php?$query_string; \
        } \
        \
        location ~ \.php$ { \
            fastcgi_pass 127.0.0.1:9000; \
            fastcgi_index index.php; \
            include fastcgi_params; \
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        } \
    } \
}' > /etc/nginx/nginx.conf

########################################
# Opcache
########################################
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

EXPOSE 80

########################################
# Start services
########################################
CMD php-fpm -D && nginx -g "daemon off;"
