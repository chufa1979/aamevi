@props(['title', 'open' => false])

{{--
    Sección desplegable con <details>/<summary> nativo: sin JS, funciona con
    teclado y lectores de pantalla de fábrica. Encaja con el resto del sitio,
    que evita dependencias nuevas para interacciones simples (ver el menú
    mobile y el widget de login, ambos con data-attributes propios).
--}}
<details @if ($open) open @endif class="card group mb-4 overflow-hidden">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 font-medium">
        {{ $title }}
        <x-ui.icon name="chevron-down" class="h-4 w-4 shrink-0 transition-transform group-open:rotate-180" />
    </summary>

    <div class="border-t border-line p-5 pt-4">
        {{ $slot }}
    </div>
</details>
