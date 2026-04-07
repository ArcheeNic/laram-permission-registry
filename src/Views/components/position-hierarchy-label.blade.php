@props(['position'])
@php
    $__segments = explode(' -> ', $position->hierarchyPathLabel());
    $__escaped = array_map(static fn (string $s): string => e($s), $__segments);
@endphp
{!! implode(' -> ', $__escaped) !!}
