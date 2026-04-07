.PHONY: build install test convert demo clean

# Сборка образа Docker
build:
	docker compose build

# Установка зависимостей
install:
	docker compose run --rm install

# Запуск тестов
test:
	docker compose run --rm test

# Конвертация модуля из Laravel
convert:
	mkdir -p demo
	chmod +x install.sh
	docker compose run --rm app ./install.sh

# Запуск демонстрационного приложения
demo:
	mkdir -p demo
	docker compose up laravel-demo

# Доступ к контейнеру
shell:
	docker compose run --rm app bash

# Проверка стиля кода
style:
	docker compose run --rm app ./vendor/bin/php-cs-fixer fix --dry-run --diff

# Исправление стиля кода
fix:
	docker compose run --rm app ./vendor/bin/php-cs-fixer fix

# Очистка
clean:
	docker compose down -v
	rm -rf demo
	rm -rf vendor
	rm -rf composer.lock