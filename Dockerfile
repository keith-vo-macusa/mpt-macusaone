########################################
# Stage 1: Vendor (PHP 8.3)
########################################
FROM php:8.3-cli-alpine AS vendor

# Cài composer + extension cần thiết
RUN apk add --no-cache \
    git \
    unzip \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    curl

# Install PHP extensions
RUN docker-php-ext-install intl zip

# Install composer
RUN curl -sS https://getcomposer.org/installer | php \
    -- --install-dir=/usr/local/bin --filename=composer

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

RUN apk add --no-cache \
    nginx \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    bash

RUN docker-php-ext-install \
    intl \
    pdo \
    pdo_mysql \
    zip

WORKDIR /var/www/html

COPY . /var/www/html
COPY --from=vendor /app/vendor /var/www/html/vendor

RUN composer dump-autoload \
    --no-dev \
    --optimize

RUN mkdir -p storage/logs bootstrap/cache

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# nginx inline config
RUN rm /etc/nginx/nginx.conf

RUN echo 'events {} \
http { \
    server { \
        listen 80; \
        root /var/www/html/public; \
        index index.php index.html; \
        location / { \
            try_files $uri $uri/ /index.php?$query_string; \
        } \
        location ~ \.php$ { \
            fastcgi_pass 127.0.0.1:9000; \
            fastcgi_index index.php; \
            include fastcgi_params; \
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        } \
    } \
}' > /etc/nginx/nginx.conf

EXPOSE 80

CMD php-fpm -D && nginx -g "daemon off;"
