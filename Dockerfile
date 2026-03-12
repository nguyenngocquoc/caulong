FROM php:7.4-fpm

# Cài các extension cần cho MySQL + PDO
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Bật hiển thị lỗi trong môi trường phát triển
COPY php-development.ini /usr/local/etc/php/conf.d/99-development.ini