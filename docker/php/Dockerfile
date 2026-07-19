FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev libicu-dev default-mysql-client gzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl dom xml \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# Persian fonts for invoice PDF (Vazirmatn)
RUN mkdir -p /var/www/resources/fonts \
    && (test -f /var/www/resources/fonts/Vazirmatn-Regular.ttf || \
        curl -fsSL -o /var/www/resources/fonts/Vazirmatn-Regular.ttf \
          https://github.com/rastikerdar/vazirmatn/raw/master/fonts/ttf/Vazirmatn-Regular.ttf) \
    && (test -f /var/www/resources/fonts/Vazirmatn-Bold.ttf || \
        curl -fsSL -o /var/www/resources/fonts/Vazirmatn-Bold.ttf \
          https://github.com/rastikerdar/vazirmatn/raw/master/fonts/ttf/Vazirmatn-Bold.ttf)

RUN composer install --no-dev --optimize-autoloader --no-interaction || true

RUN chmod +x /var/www/docker-entrypoint.sh \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
ENTRYPOINT ["/var/www/docker-entrypoint.sh"]
CMD ["php-fpm"]
