{{--
    Barra superior de cortesía, equivalente al `#top` del sitio madre: texto
    pequeño alineado a la derecha. El home ahora tiene vidriera pública, así
    que acá puede no haber usuario — en ese caso se ofrece el widget de login
    en vez del saludo.
--}}
<div class="container-site flex w-full flex-wrap items-center justify-end gap-3 pt-5 text-xs">
    <x-preferences />

    @auth
        <span>Hola, {{ auth()->user()->first_name }}</span>

        @if (auth()->user()->isAdmin())
            <a href="/admin" class="underline-offset-2 hover:underline hover:text-accent">Administración</a>
        @elseif (auth()->user()->isTeacher())
            <a href="/profesores" class="underline-offset-2 hover:underline hover:text-accent">Mis cursos</a>
        @elseif (auth()->user()->isRegistrar())
            <a href="/administracion" class="underline-offset-2 hover:underline hover:text-accent">Solicitudes</a>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="cursor-pointer underline-offset-2 hover:underline">
                Cerrar sesión
            </button>
        </form>
    @else
        <x-login-widget />
    @endauth
</div>
