<?php

namespace Artprog\PermissionRegistry\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    /**
     * Получить имя таблицы из конфигурации
     */
    public function getTable()
    {
        $baseTable = parent::getTable();
        $configKey = 'permission-registry.tables.' . $baseTable;

        return config($configKey, $baseTable);
    }
}