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
    @endswitch
</svg>
