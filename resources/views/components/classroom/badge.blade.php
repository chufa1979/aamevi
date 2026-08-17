@props(['label', 'color' => 'gray', 'icon' => null])

{{--
    Etiqueta de estado genérica.

    `state-badge` es la de las clases y está atada a `ClassProgressState`; ésta
    recibe los datos sueltos y sirve para cualquier otro estado —hoy, el de una
    consulta—. Mismos colores y misma forma, para que el vocabulario visual del
    aula sea uno solo.
--}}
@php
    $colores = [
        'success' => 'text-primary-800 bg-primary-50',
        'warning' => 'text-accent-800 bg-accent-50',
        'danger' => 'text-red-800 bg-red-50',
        'info' => 'text-blue-900 bg-blue-50',
        'gray' => 'text-subtle bg-canvas',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium '
        .($colores[$color] ?? $colores['gray']),
]) }}>
    @if ($icon)
        <x-ui.icon :name="$icon" class="h-3.5 w-3.5" />
    @endif

    {{ $label }}
</span>
