FROM php:8.4-fpm
# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip

# Установка расширений PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# Права на папки
# Сначала создаем папки, затем меняем владельца
RUN mkdir -p /var/www/storage /var/www/cache && \
    chown -R www-data:www-data /var/www/storage /var/www/cache

EXPOSE 9000
CMD ["php-fpm"]