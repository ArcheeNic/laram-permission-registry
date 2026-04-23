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
- Поля доступа с валидацией значений (глобальные и специфичные)
- События жизненного цикла для интеграции с другими системами
- Триггеры для автоматизации действий при выдаче/отзыве прав (см. [TRIGGERS.md](TRIGGERS.md))
- Plug-in виджеты для расширения UI host-приложением (см. [WIDGETS.md](WIDGETS.md))
- Удобный пользовательский интерфейс на Livewire

## Naming Convention

**Важно:** Модуль использует таблицу `virtual_users` для хранения пользователей вместо стандартной таблицы `users`. Это сделано для избежания конфликтов с пользовательской таблицей `users` в приложениях, где подключается модуль.

Все связанные с пользователями таблицы и поля используют префикс `virtual_user`:
- Таблица пользователей: `virtual_users`
- Глобальные поля пользователей: `virtual_user_field_values`
- Pivot-таблицы: `virtual_user_groups`, `virtual_user_positions`
- Внешние ключи: `virtual_user_id`

При интеграции модуля в ваше приложение используйте модель `VirtualUser` из пакета для работы с пользователями в контексте системы прав доступа.

### Глобальные и специфичные поля

Модуль поддерживает два типа полей прав доступа:

**Глобальные поля** (`is_global = true`):
- Значения хранятся один раз для пользователя в таблице `virtual_user_field_values`
- Используются во всех правах, где они требуются
- Всегда обязательны к заполнению при выдаче права
- Примеры: имя, фамилия, телефон, email

**Специфичные поля** (`is_global = false`):
- Значения хранятся отдельно для каждого выданного права в таблице `granted_permission_field_values`
- Уникальны для конкретного права доступа
- Примеры: SSH ключ для конкретного сервера, логин в конкретной системе

## Лицензия

Этот пакет распространяется под лицензией MIT.


