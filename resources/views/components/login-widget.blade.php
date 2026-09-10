{{--
    Login desde el header, para quien todavía no inició sesión. Un botón que
    despliega un panel angosto con el mismo formulario de `auth.login`, sin
    salir de la página en la que está — pensado para quien llegó a la ficha de
    un curso y quiere anotarse sin perder ese lugar (la sesión guarda el
    destino en `url.intended` y `AuthenticatedSessionController` lo respeta).

    El JS que abre/cierra el panel está en `resources/js/login-widget.js`,
    mismo patrón data-attribute que el menú mobile.
--}}
<div class="relative">
    <button type="button" data-login-toggle
            class="cursor-pointer underline-offset-2 hover:underline hover:text-accent"
            aria-expanded="false" aria-controls="login-panel">
        Iniciar sesión
    </button>

    <div id="login-panel" data-login-panel
         class="invisible absolute right-0 top-full z-20 mt-2 w-72 scale-95 bg-canvas p-5 text-left
                text-xs opacity-0 shadow-lg transition-[opacity,transform] duration-150
                border-t-[3px] border-primary">
        @if ($errors->any())
            <p class="mb-3 text-error">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <input type="hidden" name="next" value="{{ url()->current() }}">

            <div class="mb-3">
                <label for="header-email" class="field-label">Correo electrónico</label>
                <input id="header-email" name="email" type="email" class="field"
                       required autocomplete="username">
            </div>

            <div class="mb-4">
                <label for="header-password" class="field-label">Contraseña</label>
                <input id="header-password" name="password" type="password" class="field"
                       required autocomplete="current-password">
            </div>

            <x-button type="submit" class="w-full">Ingresar</x-button>
        </form>

        <p class="mt-4 text-center">
            ¿Todavía no tenés cuenta?
            <a href="{{ route('register') }}" class="underline underline-offset-2 hover:text-accent">Creá una</a>
        </p>
    </div>
</div>
