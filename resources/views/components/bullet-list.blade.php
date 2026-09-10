@props(['text'])

{{--
    Los campos de la ficha pública que son «un ítem por línea» —cuerpo
    docente, objetivos, requisitos de inscripción— se cargan como texto plano
    en el panel (ver helperText de esos campos en CourseForm) y se listan acá.
    No hace falta un editor de texto enriquecido para una lista de líneas.
--}}
@php
    $items = collect(preg_split('/\r\n|\r|\n/', (string) $text))
        ->map(fn (string $linea): string => trim($linea))
        ->filter();
@endphp

@if ($items->isNotEmpty())
    <ul class="grid gap-2 text-sm leading-relaxed">
        @foreach ($items as $item)
            <li class="flex gap-2">
                <span class="text-primary" aria-hidden="true">—</span>
                <span>{{ $item }}</span>
            </li>
        @endforeach
    </ul>
@endif
