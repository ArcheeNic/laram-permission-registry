<?php

namespace ArcheeNic\PermissionRegistry\Widgets;

interface WidgetInterface
{
    public function slot(): string;

    /**
     * @param  array<string, mixed>  $context
     */
    public function shouldRender(array $context): bool;

    public function component(): string;

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function props(array $context): array;
}
