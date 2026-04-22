<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Permission Registry Table Names
    |--------------------------------------------------------------------------
    |
    | Здесь можно настроить названия таблиц, используемые модулем.
    | Это полезно, если у вас возникают конфликты с существующими таблицами.
    |
    */
    'tables' => [
        'permissions' => 'permissions',
        'permission_fields' => 'permission_fields',
        'permission_permission_field' => 'permission_permission_field',
        'permission_groups' => 'permission_groups',
        'permission_permission_group' => 'permission_permission_group',
        'positions' => 'positions',
        'position_permission' => 'position_permission',
        'position_permission_group' => 'position_permission_group',
        'granted_permissions' => 'granted_permissions',
        'granted_permission_field_values' => 'granted_permission_field_values',
        'virtual_user_groups' => 'virtual_user_groups',
        'virtual_user_positions' => 'virtual_user_positions',
        'virtual_users' => 'virtual_users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Middlewares
    |--------------------------------------------------------------------------
    |
    | Здесь можно настроить middleware, используемые для маршрутов модуля.
    |
    */
    'middlewares' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Display Name Template
    |--------------------------------------------------------------------------
    |
    | Template for generating user display name from global fields.
    | Use field IDs in curly braces, e.g., {1} {2} where 1,2 are permission_field IDs
    |
    | Example: '{1} {2}' will generate "John Doe" if field 1 is "John" and field 2 is "Doe"
    |
    */
    'display_name_template' => env('PERMISSION_REGISTRY_DISPLAY_NAME_TEMPLATE', '{1} {2}'),

    /*
    |--------------------------------------------------------------------------
    | User to Virtual User resolver
    |--------------------------------------------------------------------------
    |
    | Resolves application user id (users.id) to virtual user id (virtual_users.id).
    | Used for approval flow: who can approve is defined by policy (virtual users/positions),
    | but the actual approver stored in DB is the real user (users.id).
    |
    */
    'user_resolver' => \ArcheeNic\PermissionRegistry\Support\DefaultUserToVirtualUserResolver::class,

    /*
    |--------------------------------------------------------------------------
    | Application User Model
    |--------------------------------------------------------------------------
    |
    | Eloquent model class for application users (e.g. App\Models\User).
    | Used for ApprovalDecision->approver() relationship when approver_id stores user_id.
    |
    */
    'user_model' => null,

    /*
    |--------------------------------------------------------------------------
    | Widgets
    |--------------------------------------------------------------------------
    |
    | Список классов-виджетов, встраиваемых в слоты UI (например,
    | "user.card.actions"). Каждый класс должен реализовывать
    | ArcheeNic\PermissionRegistry\Widgets\WidgetInterface.
    |
    */
    'widgets' => [
        //
    ],
];