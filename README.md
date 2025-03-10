# Permission Registry для Laravel

Модуль для централизованного управления доступами пользователей к различным сервисам и ресурсам. Обеспечивает гибкий механизм определения прав доступа через систему доступов, полей, групп и должностей.

## Установка

Вы можете установить пакет через Composer:

```bash
composer require archee-nic/permission-registry
```

После установки опубликуйте миграции и запустите их:

```bash
php artisan vendor:publish --tag=permission-registry-migrations
php artisan migrate
```

При желании вы можете опубликовать конфигурационный файл:

```bash
php artisan vendor:publish --tag=permission-registry-config
```

Вы также можете опубликовать представления для их настройки:

```bash
php artisan vendor:publish --tag=permission-registry-views
```

## Использование

### Проверка доступа

```php
use ArcheeNic\PermissionRegistry\Facades\PermissionRegistry;

// Проверка прав доступа
if (PermissionRegistry::hasPermission($userId, 'service_name', 'permission_name')) {
    // Пользователь имеет доступ
}

// Валидация значения поля доступа
if (PermissionRegistry::validateField($userId, 'service_name', 'permission_name', 'field_name', 'value')) {
    // Значение поля валидно
}
```

### Управление доступами

```php
// Получение всех доступов пользователя
$permissions = PermissionRegistry::getUserPermissions($userId);

// Выдача доступа
PermissionRegistry::grantPermission(
    $userId,
    'service_name',
    'permission_name',
    ['field_id' => 'value'],  // значения полей
    ['reason' => 'Requested by admin'],  // метаданные
    '2023-12-31 23:59:59'  // срок действия
);

// Отзыв доступа
PermissionRegistry::revokePermission($userId, 'service_name', 'permission_name');

// Синхронизация доступов на основе должностей и групп пользователя
PermissionRegistry::syncUserPermissions($userId);
```

### Middleware

Пакет регистрирует middleware `permission`, который можно использовать для защиты маршрутов:

```php
Route::middleware(['permission:service_name,permission_name'])->group(function () {
    // Маршруты, защищенные проверкой прав
});
```

## Особенности

- Централизованное управление правами доступа
- Поддержка должностей с наследованием прав
- Группировка прав для удобного управления
- Поля доступа с валидацией значений
- События жизненного цикла для интеграции с другими системами
- Удобный пользовательский интерфейс на Livewire

## Лицензия

Этот пакет распространяется под лицензией MIT.


