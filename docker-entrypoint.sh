#!/bin/bash
set -e

# Функция вывода информационных сообщений
info() {
    echo "[INFO] $1"
}

# Функция ожидания запуска базы данных
wait_for_db() {
    info "Ожидание запуска базы данных..."
    attempt=1
    max_attempts=30

    until php -r "try {new PDO('mysql:host=$DB_HOST;dbname=$DB_DATABASE', '$DB_USERNAME', '$DB_PASSWORD');} catch (PDOException \$e) {echo \$e->getMessage() . PHP_EOL; exit(1);}" >/dev/null 2>&1; do
        if [ $attempt -gt $max_attempts ]; then
            info "Невозможно подключиться к базе данных после $max_attempts попыток. Выход."
            exit 1
        fi

        info "Ожидание готовности базы данных... ($attempt/$max_attempts)"
        attempt=$((attempt + 1))
        sleep 2
    done

    info "База данных готова!"
}

# Установка зависимостей
composer_install() {
    info "Установка зависимостей Composer..."
    composer install --no-interaction --no-progress
}

# Запуск миграций
run_migrations() {
    info "Запуск миграций..."
    php artisan migrate --force
}

# Запуск тестов
run_tests() {
    info "Запуск тестов..."
    vendor/bin/phpunit
}

# Запуск демонстрационного приложения Laravel
run_laravel_demo() {
    info "Запуск демонстрационного приложения..."
    cd /demo
    composer create-project --prefer-dist laravel/laravel .
    composer config repositories.local path /app
    composer require artprog/permission-registry:@dev
    php artisan vendor:publish --tag=permission-registry-migrations
    php artisan vendor:publish --tag=permission-registry-config
    php artisan migrate
    php artisan serve --host=0.0.0.0
}

# Точка входа с выбором команды
case "$1" in
    "wait")
        # Ожидание запуска других служб
        sleep 5
        ;;
    "install")
        composer_install
        ;;
    "test")
        composer_install
        run_tests
        ;;
    "demo")
        run_laravel_demo
        ;;
    *)
        # Запуск указанной команды
        exec "$@"
        ;;
esac