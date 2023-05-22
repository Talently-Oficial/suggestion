# Image base
FROM php:7.4-fpm

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    bash \
    build-essential \
    curl \
    git \
    libonig-dev \
    libzip-dev \
    zip

# Instalar extensiones PHP
RUN docker-php-ext-install mbstring zip exif pcntl

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www