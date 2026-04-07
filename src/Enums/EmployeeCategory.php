<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum EmployeeCategory: string
{
    case STAFF = 'staff';
    case CONTRACTOR = 'contractor';

    public function labelKey(): string
    {
        return match ($this) {
            self::STAFF => 'permission-registry::messages.employee_category_staff',
            self::CONTRACTOR => 'permission-registry::messages.employee_category_contractor',
        };
    }
}
