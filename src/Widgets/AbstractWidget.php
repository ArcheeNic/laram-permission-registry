<?php

namespace ArcheeNic\PermissionRegistry\Widgets;

abstract class AbstractWidget implements WidgetInterface
{
    abstract public function slot(): string;

    abstract public function component(): string;

    public function shouldRender(array $context): bool
    {
        return true;
    }

    public function props(array $context): array
    {
        return $context;
    }
}
