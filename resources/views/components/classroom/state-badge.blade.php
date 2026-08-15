@props(['state'])

{{--
    Estado de una clase.

    Lleva icono **y** texto a propósito: el icono solo obliga a aprender una
    leyenda, y el color solo deja afuera a quien no lo distingue. Es el mismo
    enum que usa la grilla de seguimiento del panel, así que docente y alumno
    ven el mismo vocabulario.
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
        .($colores[$state->getColor()] ?? $colores['gray']),
]) }}>
    <x-ui.icon :name="match ($state) {
        \App\Enums\ClassProgressState::Completed => 'aprobada',
        \App\Enums\ClassProgressState::InProgress => 'en-curso',
        \App\Enums\ClassProgressState::Available => 'disponible',
        \App\Enums\ClassProgressState::Scheduled => 'agendada',
        \App\Enums\ClassProgressState::Locked => 'bloqueada',
    }" class="h-3.5 w-3.5" />

    {{ $state->getLabel() }}
</span>
