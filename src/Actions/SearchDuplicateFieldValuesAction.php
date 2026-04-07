<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;
use Illuminate\Support\Facades\DB;

class SearchDuplicateFieldValuesAction
{
    public function execute(int $fieldId, string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $escaped = str_replace(['%', '_'], ['\%', '\_'], $value);

        $query = VirtualUserFieldValue::where('permission_field_id', $fieldId);

        if (DB::getDriverName() === 'pgsql') {
            $query->whereRaw("value ILIKE ? ESCAPE '\\'", [$escaped . '%']);
        } else {
            $query->whereRaw("LOWER(value) LIKE ? ESCAPE '\\'", [mb_strtolower($escaped) . '%']);
        }

        return $query->distinct('virtual_user_id')
            ->count('virtual_user_id');
    }
}
