@props(['name', 'context' => []])

@php
    $registry = app(\ArcheeNic\PermissionRegistry\Widgets\WidgetRegistry::class);
    $widgets = $registry->forSlot($name, $context);
@endphp

@foreach($widgets as $widget)
    @livewire($widget->component(), $widget->props($context), key('widget-'.$name.'-'.$loop->index))
@endforeach
