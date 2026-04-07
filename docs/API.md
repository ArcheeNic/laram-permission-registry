# Permission Registry API Documentation

## Обзор

Permission Registry API предоставляет REST endpoints для управления пользователями, правами доступа, должностями и группами.

## Аутентификация

API использует Laravel Sanctum для аутентификации. Необходимо передавать Bearer token в заголовке `Authorization`:

```
Authorization: Bearer {your-token}
```

## Base URL

```
/api/permission-registry
```

## Endpoints

### Users

#### POST /users
Создание нового пользователя.

**Request Body:**
```json
{
  "global_fields": {
    "1": "ivan@example.com",
    "2": "+79001234567",
    "3": "Иван",
    "4": "Иванов"
  }
}
```

**Response (201):**
```json
{
  "data": {
    "id": 1,
    "name": "Иван Иванов",
    "meta": null,
    "created_at": "2025-12-10T12:00:00.000000Z",
    "updated_at": "2025-12-10T12:00:00.000000Z",
    "positions": [],
    "groups": [],
    "field_values": [...]
  }
}
```

**Примечания:**
- Имя пользователя генерируется автоматически на основе глобальных полей
- `global_fields` - объект где ключ это `field_id`, значение - значение поля

---

### Permissions

#### POST /users/{user}/permissions
Выдача права пользователю.

**URL Parameters:**
- `user` (integer) - ID пользователя

**Request Body:**
```json
{
  "permission_id": 5,
  "field_values": {
    "10": "ssh-rsa AAAAB3...",
    "11": "developer"
  },
  "meta": {
    "reason": "Requested by admin"
  },
  "expires_at": "2026-12-31 23:59:59",
  "skip_triggers": false,
  "execute_sync": false
}
```

**Parameters:**
- `permission_id` (integer, required) - ID права доступа
- `field_values` (object, optional) - Значения полей права в формате field_id => value
- `meta` (object, optional) - Дополнительные метаданные
- `expires_at` (string, optional) - Дата истечения права (format: Y-m-d H:i:s)
- `skip_triggers` (boolean, optional, default: false) - Пропустить выполнение триггеров
- `execute_sync` (boolean, optional, default: false) - Выполнить триггеры синхронно

**Response (201):**
```json
{
  "data": {
    "id": 1,
    "permission": {
      "id": 5,
      "service": "gitlab",
      "name": "developer_access",
      "description": "Access to GitLab as developer"
    },
    "status": "pending",
    "status_message": null,
    "enabled": true,
    "field_values": [],
    "granted_at": "2025-12-10T12:00:00.000000Z",
    "expires_at": "2026-12-31T23:59:59.000000Z"
  }
}
```

**Статусы:**
- `pending` - Ожидает выполнения триггеров
- `granted` - Право выдано успешно
- `failed` - Ошибка при выполнении триггеров

---

#### DELETE /users/{user}/permissions/{permission}
Отзыв права у пользователя.

**URL Parameters:**
- `user` (integer) - ID пользователя
- `permission` (integer) - ID выданного права (granted_permission_id)

**Response (204):**
Без тела ответа при успешном отзыве.

---

### Positions

#### POST /users/{user}/positions
Назначение должности пользователю.

**URL Parameters:**
- `user` (integer) - ID пользователя

**Request Body:**
```json
{
  "position_id": 3
}
```

**Response (200):**
```json
{
  "message": "Position assigned successfully",
  "user": {
    "data": {
      "id": 1,
      "name": "Иван Иванов",
      "positions": [...],
      "groups": [],
      "granted_permissions": [...]
    }
  }
}
```

**Автоматическая выдача прав:**
При назначении должности автоматически выдаются все права из этой должности (включая родительские) и её групп, у которых установлен флаг `auto_grant = true`.

---

#### DELETE /users/{user}/positions/{position}
Отзыв должности у пользователя.

**URL Parameters:**
- `user` (integer) - ID пользователя
- `position` (integer) - ID должности

**Response (204):**
Без тела ответа при успешном отзыве.

**Автоматический отзыв прав:**
При отзыве должности автоматически отзываются все права из этой должности (включая родительские) и её групп, у которых установлен флаг `auto_revoke = true`.

---

### Groups

#### POST /users/{user}/groups
Назначение группы пользователю.

**URL Parameters:**
- `user` (integer) - ID пользователя

**Request Body:**
```json
{
  "group_id": 2
}
```

**Response (200):**
```json
{
  "message": "Group assigned successfully",
  "user": {
    "data": {
      "id": 1,
      "name": "Иван Иванов",
      "positions": [],
      "groups": [...],
      "granted_permissions": [...]
    }
  }
}
```

**Автоматическая выдача прав:**
При назначении группы автоматически выдаются все права из этой группы, у которых установлен флаг `auto_grant = true`.

---

#### DELETE /users/{user}/groups/{group}
Отзыв группы у пользователя.

**URL Parameters:**
- `user` (integer) - ID пользователя
- `group` (integer) - ID группы

**Response (204):**
Без тела ответа при успешном отзыве.

**Автоматический отзыв прав:**
При отзыве группы автоматически отзываются все права из этой группы, у которых установлен флаг `auto_revoke = true`.

---

## HTTP Status Codes

- `200 OK` - Успешный запрос
- `201 Created` - Ресурс успешно создан
- `204 No Content` - Успешное удаление без тела ответа
- `401 Unauthorized` - Не авторизован (нет или невалидный токен)
- `404 Not Found` - Ресурс не найден
- `422 Unprocessable Entity` - Ошибка валидации

## Validation Errors

При ошибках валидации API возвращает ответ с кодом `422` и следующей структурой:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "permission_id": [
      "The permission id field is required."
    ],
    "field_values.10": [
      "The field value is required."
    ]
  }
}
```

## Auto Grant/Revoke Feature

### Концепция

Права доступа могут иметь флаги `auto_grant` и `auto_revoke`, которые управляют автоматической выдачей и отзывом прав при работе с должностями и группами.

### Когда работает auto_grant

При назначении должности или группы пользователю система:
1. Получает все права из назначаемой должности/группы
2. Для должностей также проверяет родительские должности рекурсивно
3. Для должностей также проверяет все группы, связанные с должностью
4. Фильтрует только те права, у которых `auto_grant = true`
5. Автоматически выдает эти права пользователю (без выполнения триггеров)

### Когда работает auto_revoke

При отзыве должности или группы у пользователя система:
1. Получает все права из отзываемой должности/группы
2. Для должностей также проверяет родительские должности рекурсивно
3. Для должностей также проверяет все группы, связанные с должностью
4. Фильтрует только те права, у которых `auto_revoke = true`
5. Автоматически отзывает эти права у пользователя (без выполнения триггеров)

### Пример использования

```json
// Настройка права с автовыдачей
{
  "service": "gitlab",
  "name": "basic_access",
  "auto_grant": true,
  "auto_revoke": true
}

// При назначении должности "Developer", содержащей это право:
POST /users/1/positions
{
  "position_id": 3
}

// Пользователь автоматически получит право "gitlab.basic_access"
// При отзыве должности право будет автоматически отозвано
```

## Examples with cURL

### Create User
```bash
curl -X POST http://localhost/api/permission-registry/users \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "global_fields": {
      "1": "ivan@example.com",
      "2": "+79001234567"
    }
  }'
```

### Grant Permission
```bash
curl -X POST http://localhost/api/permission-registry/users/1/permissions \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "permission_id": 5,
    "field_values": {
      "10": "some-value"
    },
    "expires_at": "2026-12-31 23:59:59"
  }'
```

### Assign Position
```bash
curl -X POST http://localhost/api/permission-registry/users/1/positions \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "position_id": 3
  }'
```

### Revoke Permission
```bash
curl -X DELETE http://localhost/api/permission-registry/users/1/permissions/15 \
  -H "Authorization: Bearer {token}"
```

## Testing

Для тестирования API рекомендуется использовать:
- **Postman** - импортировать OpenAPI спецификацию из `permission-registry-api.json`
- **Apidog** - импортировать документацию напрямую
- **Insomnia** - поддерживает OpenAPI 3.0

## Rate Limiting

API использует стандартное rate limiting Laravel (60 запросов в минуту для аутентифицированных пользователей).

## Versioning

Текущая версия API: **v1.0.0**

При breaking changes будет создана новая версия с префиксом `/api/v2/permission-registry`.
