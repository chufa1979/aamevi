@props(['html' => null])

{{--
    Muestra el marcado que viene del editor del panel, ya limpio.

    Es el único lugar del proyecto que imprime HTML sin escapar. Tenerlo acá y
    no repartido en `{!! !!}` por las vistas es lo que hace que no dependa de
    que nadie se olvide: si aparece un `{!! !!}` suelto en el aula, es un bug.

    Las clases de `.prose-aamevi` dan el espaciado de párrafos y listas, que sin
    ellas Tailwind deja en cero.
--}}
@php($limpio = \App\Support\Html::sanitize($html))

@if (filled($limpio))
    <div {{ $attributes->merge(['class' => 'prose-aamevi']) }}>{!! $limpio !!}</div>
@endif
