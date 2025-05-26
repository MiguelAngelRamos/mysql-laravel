# ----------------------------------------------------
# Dockerfile para Laravel con PHP 8.2 y MySQL (pdo_mysql)
# ----------------------------------------------------

# Etapa 1: Construcción con Composer
FROM composer:2 AS composer_stage

WORKDIR /app

# Copiamos solo composer.json y composer.lock para aprovechar caché
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --optimize-autoloader

# Luego copiamos todo el proyecto
COPY . .

# Etapa 2: Imagen final con PHP 8.2 y extensiones necesarias
FROM php:8.2-cli-alpine

# Instalación de dependencias del sistema y extensiones de PHP
RUN apk add --no-cache \
        bash \
        mysql-client \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        zip \
        intl

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Copiar la aplicación desde la etapa anterior
COPY --from=composer_stage /app /var/www/html

# Exponer puerto 80 para la app (usando Artisan Serve)
EXPOSE 80

# Comando por defecto: iniciar servidor de Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
