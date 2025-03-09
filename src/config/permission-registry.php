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
        'user_groups' => 'user_groups',
        'user_positions' => 'user_positions',
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
];