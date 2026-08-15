{{--
    Barra superior de cortesía, equivalente al `#top` del sitio madre: texto
    pequeño alineado a la derecha. Como todo el layout está detrás de `auth`,
    acá siempre hay usuario; muestra quién es y permite cerrar sesión.
--}}
<div class="container-site flex w-full flex-wrap items-center justify-end gap-3 pt-5 text-xs">
    <x-preferences />

    <span>Hola, {{ auth()->user()->first_name }}</span>

    @if (auth()->user()->isAdmin())
        <a href="/admin" class="underline-offset-2 hover:underline hover:text-accent">Administración</a>
    @elseif (auth()->user()->isTeacher())
        <a href="/profesores" class="underline-offset-2 hover:underline hover:text-accent">Mis cursos</a>
    @endif

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="cursor-pointer underline-offset-2 hover:underline">
            Cerrar sesión
        </button>
    </form>
</div>
