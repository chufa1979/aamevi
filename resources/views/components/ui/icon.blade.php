@props(['name'])

{{--
    Íconos de interfaz. En la versión React se tomaban de `react-icons/fa`; acá
    van inline para no arrastrar una dependencia de JS por seis glifos.

    Los marcadores de redes (instagram, linkedin, whatsapp) son construcciones
    geométricas simplificadas, no los logos oficiales. Si se necesita fidelidad
    de marca, reemplazarlos por los SVG oficiales de cada red.
--}}
<svg {{ $attributes->merge(['class' => 'h-[1em] w-[1em]', 'aria-hidden' => 'true']) }}
     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
     stroke-linecap="round" stroke-linejoin="round">
    @switch($name)
        @case('menu')
            <line x1="3" y1="6" x2="21" y2="6" />
            <line x1="3" y1="12" x2="21" y2="12" />
            <line x1="3" y1="18" x2="21" y2="18" />
            @break

        @case('close')
            <line x1="5" y1="5" x2="19" y2="19" />
            <line x1="19" y1="5" x2="5" y2="19" />
            @break

        @case('search')
            <circle cx="11" cy="11" r="7" />
            <line x1="16.2" y1="16.2" x2="21" y2="21" />
            @break

        @case('email')
            <rect x="2" y="4" width="20" height="16" rx="2" />
            <polyline points="2.5,5.5 12,13 21.5,5.5" />
            @break

        @case('whatsapp')
            <path d="M3.5 20.5l1.3-4.2A8.2 8.2 0 1 1 8 19.4l-4.5 1.1z" />
            <path d="M9 9.2c.3 1 .8 1.9 1.5 2.6.7.7 1.6 1.2 2.6 1.5l.9-1.1 1.9.8-.4 1.5c-1.9.3-4-.7-5.5-2.2S8 8.7 8.3 6.8l1.5-.4.8 1.9-.6.9z" />
            @break

        @case('instagram')
            <rect x="3" y="3" width="18" height="18" rx="5" />
            <circle cx="12" cy="12" r="4" />
            <circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none" />
            @break

        @case('linkedin')
            <rect x="3" y="3" width="18" height="18" rx="2" />
            <line x1="7.5" y1="10.5" x2="7.5" y2="17" />
            <circle cx="7.5" cy="7.2" r="1" fill="currentColor" stroke="none" />
            <path d="M11.5 17v-6.5M11.5 13a2.5 2.5 0 0 1 5 0V17" />
            @break

        {{-- Preferencias de lectura --}}
        @case('sol')
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
            @break

        @case('luna')
            <path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5z" />
            @break

        {{-- Tipos de material de una clase --}}
        @case('video')
            <rect x="2" y="5" width="14" height="14" rx="2" />
            <polygon points="16,10 22,7 22,17 16,14" fill="currentColor" stroke="none" />
            @break

        @case('pdf')
            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
            <polyline points="14,3 14,8 19,8" />
            <line x1="9" y1="13" x2="15" y2="13" />
            <line x1="9" y1="17" x2="13" y2="17" />
            @break

        @case('texto')
            <line x1="4" y1="7" x2="20" y2="7" />
            <line x1="4" y1="12" x2="20" y2="12" />
            <line x1="4" y1="17" x2="14" y2="17" />
            @break

        @case('tarea')
            <rect x="4" y="4" width="16" height="17" rx="2" />
            <path d="M9 2h6v4H9z" />
            <polyline points="8.5,13 11,15.5 15.5,10.5" />
            @break

        {{-- Estados de una clase --}}
        @case('aprobada')
            <circle cx="12" cy="12" r="9" />
            <polyline points="8,12.5 11,15.5 16,9.5" />
            @break

        @case('en-curso')
            <circle cx="12" cy="12" r="9" />
            <polygon points="10.5,8.5 16,12 10.5,15.5" fill="currentColor" stroke="none" />
            @break

        @case('disponible')
            <circle cx="12" cy="12" r="9" />
            @break

        @case('bloqueada')
            <rect x="5" y="11" width="14" height="10" rx="2" />
            <path d="M8 11V8a4 4 0 0 1 8 0v3" />
            @break

        {{-- Respuesta errada. No es el candado: ahí no hay nada bloqueado --}}
        @case('incorrecta')
            <circle cx="12" cy="12" r="9" />
            <line x1="9" y1="9" x2="15" y2="15" />
            <line x1="15" y1="9" x2="9" y2="15" />
            @break

        @case('agendada')
            <circle cx="12" cy="12" r="9" />
            <polyline points="12,7 12,12 15.5,14" />
            @break

        @case('evaluacion')
            <circle cx="12" cy="12" r="9" />
            <path d="M9.5 9.5a2.6 2.6 0 1 1 3.2 2.5c-.5.2-.7.6-.7 1.1v.4" />
            <circle cx="12" cy="16.8" r="1" fill="currentColor" stroke="none" />
            @break

        @case('vivo')
            <circle cx="12" cy="12" r="3" fill="currentColor" stroke="none" />
            <path d="M7.5 7.5a6.4 6.4 0 0 0 0 9M16.5 16.5a6.4 6.4 0 0 0 0-9" />
            @break

        @case('volver')
            <line x1="20" y1="12" x2="5" y2="12" />
            <polyline points="11,6 5,12 11,18" />
            @break
    @endswitch
</svg>
