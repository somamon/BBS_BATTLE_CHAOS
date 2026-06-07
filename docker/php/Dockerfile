FROM php:8.3-fpm-alpine
# git/unzip は本番サーバーでの composer install（git clone デプロイ）に必要
RUN apk add --no-cache git unzip \
 && docker-php-ext-install pdo pdo_mysql
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html