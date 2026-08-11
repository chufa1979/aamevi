@props([
    'variant' => 'primary',
    /* Versión en cursiva usada en las llamadas a la acción del sitio institucional. */
    'cta' => false,
    /* Con `href` se renderiza como enlace; sin él, como <button>. */
    'href' => null,
    'type' => 'button',
])

@php
    $classes = [
        'primary' => 'btn',
        'accent' => 'btn-accent',
        'danger' => 'btn-danger',
        'ghost' => 'btn-ghost',
    ][$variant] ?? 'btn';

    $classes = $cta ? "{$classes} btn-cta" : $classes;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
