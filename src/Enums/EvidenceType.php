<?php

namespace ArcheeNic\PermissionRegistry\Enums;

enum EvidenceType: string
{
    case URL = 'url';
    case TICKET = 'ticket';
    case FILE = 'file';
    case COMMENT = 'comment';
    case SCREENSHOT = 'screenshot';

    public function label(): string
    {
        return match ($this) {
            self::URL => __('permission-registry::governance.evidence_type.url'),
            self::TICKET => __('permission-registry::governance.evidence_type.ticket'),
            self::FILE => __('permission-registry::governance.evidence_type.file'),
            self::COMMENT => __('permission-registry::governance.evidence_type.comment'),
            self::SCREENSHOT => __('permission-registry::governance.evidence_type.screenshot'),
        };
    }
}
