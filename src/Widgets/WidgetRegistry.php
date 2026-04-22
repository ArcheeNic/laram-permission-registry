<?php

namespace ArcheeNic\PermissionRegistry\Widgets;

use Illuminate\Contracts\Container\Container;

class WidgetRegistry
{
    /** @var array<class-string<WidgetInterface>, WidgetInterface> */
    private array $instances = [];

    public function __construct(private Container $container) {}

    /**
     * @param  class-string<WidgetInterface>|WidgetInterface  $widget
     */
    public function register(string|WidgetInterface $widget): void
    {
        if (is_string($widget)) {
            if (! is_subclass_of($widget, WidgetInterface::class)) {
                throw new \InvalidArgumentException(
                    "Widget class {$widget} must implement " . WidgetInterface::class
                );
            }
            $this->instances[$widget] = $this->container->make($widget);

            return;
        }

        $this->instances[$widget::class] = $widget;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, WidgetInterface>
     */
    public function forSlot(string $slot, array $context = []): array
    {
        $widgets = [];
        foreach ($this->instances as $widget) {
            if ($widget->slot() !== $slot) {
                continue;
            }
            if (! $widget->shouldRender($context)) {
                continue;
            }
            $widgets[] = $widget;
        }

        return $widgets;
    }

    /**
     * @return array<int, WidgetInterface>
     */
    public function all(): array
    {
        return array_values($this->instances);
    }
}
