FROM php:8.2-cli

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip pdo pdo_mysql

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создание рабочей директории
WORKDIR /app

# Копирование файлов пакета
COPY . /app

# Базовые настройки PHP
RUN echo "memory_limit=2G" > /usr/local/etc/php/conf.d/memory-limit.ini

CMD ["php", "-a"]