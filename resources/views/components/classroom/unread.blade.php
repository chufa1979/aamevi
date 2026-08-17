@props(['count' => 0])

{{--
    Cuántas cosas sin leer tiene ese ítem del menú.

    Un número y no una campanita: la campanita dice «hay algo» y el número dice
    cuánto, que es lo que decide si vale la pena entrar ahora. Se calla en cero
    en vez de mostrar un 0, que sería ruido permanente.

    `aria-label` porque el número solo, leído por un lector de pantalla, no
    significa nada al lado de «Consultas».
--}}
@if ($count > 0)
    <span class="ml-auto inline-flex min-w-5 items-center justify-center rounded-full bg-accent-700
                 px-1.5 py-0.5 text-[11px] font-medium leading-none text-white"
          aria-label="{{ $count }} sin leer">
        {{ $count > 9 ? '9+' : $count }}
    </span>
@endif
