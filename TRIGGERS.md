# Система триггеров Permission Registry

## Обзор

Система триггеров позволяет автоматизировать действия при выдаче и отзыве прав доступа.

## Основные компоненты

### 1. Миграции
- `permission_triggers` - реестр триггеров
- `permission_trigger_assignments` - привязка триггеров к правам
- `permission_dependencies` - зависимости между правами
- `permission_execution_logs` - логи выполнения триггеров
- Расширения `granted_permissions` - статусы и метаданные

### 2. Модели
- `PermissionTrigger` - триггер в реестре
- `PermissionTriggerAssignment` - привязка триггера к праву
- `PermissionDependency` - зависимость между правами
- `PermissionExecutionLog` - лог выполнения

### 3. Enums
- `GrantedPermissionStatus` - статусы прав (pending, granting, granted, revoking, revoked, failed, partially_granted)
- `TriggerEventType` - тип события (grant, revoke)
- `ExecutionLogStatus` - статус выполнения (pending, running, success, failed)

### 4. Services
- `PermissionDependencyResolver` - проверка зависимостей
- `PermissionTriggerExecutor` - выполнение триггеров

### 5. Jobs
- `GrantPermissionWorkflowJob` - асинхронная выдача прав с триггерами
- `RevokePermissionWorkflowJob` - асинхронный отзыв прав с триггерами

## Создание триггера

```php
namespace App\Triggers;

use ArcheeNic\PermissionRegistry\Contracts\PermissionTriggerInterface;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerContext;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerResult;

class MyTrigger implements PermissionTriggerInterface
{
    public function execute(TriggerContext $context): TriggerResult
    {
        try {
            // Получить глобальные поля пользователя
            $email = $context->globalFields['email'] ?? null;
            
            // Выполнить действие
            // ...
            
            return TriggerResult::success(['some_id' => 123]);
        } catch (\Exception $e) {
            return TriggerResult::failure($e->getMessage());
        }
    }
    
    public function canRollback(): bool
    {
        return true; // может откатить изменения
    }
    
    public function rollback(TriggerContext $context): void
    {
        // Откатить изменения
    }
}
```

## Использование

### 1. Регистрация триггера
Перейдите в `/permission-registry/triggers` и создайте триггер:
- Имя: "My Trigger"
- Класс: `App\Triggers\MyTrigger`
- Тип: grant/revoke/both

### 2. Привязка к праву
Откройте право → кнопка "⚡ Triggers":
- Выберите триггер из списка
- Настройте порядок выполнения (drag-and-drop)
- Включите/отключите триггер

### 3. Настройка зависимостей
Откройте право → кнопка "🔗 Dependencies":
- Добавьте требуемые права
- Укажите строгую/нестрогую зависимость

### 4. Выдача права

```php
use ArcheeNic\PermissionRegistry\Facades\PermissionRegistry;

// Выдача с триггерами (асинхронно)
PermissionRegistry::grantPermission(
    userId: 1,
    service: 'mail',
    permissionName: 'corporate_email',
    fieldValues: ['email' => 'user@example.com'],
    skipTriggers: false
);

// Выдача без триггеров (сразу)
PermissionRegistry::grantPermission(
    userId: 1,
    service: 'mail',
    permissionName: 'corporate_email',
    skipTriggers: true
);
```

## Статусы

- `pending` - ожидает выполнения триггеров
- `granting` - триггеры выполняются
- `granted` - успешно выдано
- `partially_granted` - часть триггеров упала
- `failed` - ошибка выполнения
- `revoking` - отзывается
- `revoked` - отозвано

## Примеры триггеров

В модуле есть 3 примера:
- `ExampleEmailAddTrigger` - создание корп. почты
- `ExampleBitrix24CreateUserTrigger` - создание в B24
- `ExampleGitlabAccessTrigger` - доступ в GitLab

## Зависимости

### Строгая зависимость
Право должно быть выдано (статус granted).
Пример: GitLab требует SSH ключ → право "SSH Access" должно быть выдано.

### Нестрогая зависимость
Достаточно наличия глобальных полей.
Пример: B24 требует email → достаточно заполненного поля, не обязательно выданное право "Email".
