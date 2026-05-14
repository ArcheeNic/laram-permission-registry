<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum PermissionFieldType: string
{
    case STRING = 'string';
    case EMAIL = 'email';
    case PHONE = 'phone';
    case URL = 'url';
    case DATE = 'date';
    case INTEGER = 'integer';
    case BOOLEAN = 'boolean';

    public function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        return match ($this) {
            self::EMAIL, self::URL => mb_strtolower($trimmed),
            self::PHONE => preg_replace('/\D+/', '', $trimmed) ?: null,
            self::BOOLEAN => in_array(mb_strtolower($trimmed), ['1', 'true', 'y', 'yes', 'on'], true) ? '1' : '0',
            default => $trimmed,
        };
    }
}
