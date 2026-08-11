{{--
    Barra superior de cortesía, equivalente al `#top` del sitio madre: texto
    pequeño alineado a la derecha. Como todo el layout está detrás de `auth`,
    acá siempre hay usuario; muestra quién es y permite cerrar sesión.
--}}
<div class="container-site flex w-full items-center justify-end gap-3 pt-5 text-xs">
    <span>Hola, {{ auth()->user()->first_name }}</span>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="cursor-pointer underline-offset-2 hover:underline">
            Cerrar sesión
        </button>
    </form>
</div>
