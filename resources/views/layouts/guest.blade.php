<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#00b8b3">
    <link rel="icon" type="image/png" href="/favicon.png">
    <title>@yield('title', 'Acceso') — AAMEVi</title>
    @include('partials.preferences-head')
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/preferences.js'])
</head>
{{--
    Layout de las pantallas de acceso. A diferencia de `layouts.app`, no incluye
    el menú de la plataforma: quien no inició sesión no debe ver secciones que
    de todos modos le van a rebotar en un redirect a este mismo login.

    Sí lleva un camino de vuelta al sitio —logo enlazado e «Inicio»—, porque
    desde que el home es público (ver `Public\HomeController`) hay a dónde
    volver. No lleva «Ayuda»: esa sección sigue detrás de `auth` (ver
    `routes/web.php`), así que ofrecerla acá sería un link que rebota al
    mismo login en el que ya está.
--}}
<body class="flex min-h-screen flex-col">
    <a href="#contenido" class="skip-link">Saltar al contenido</a>

    <div class="border-b-[6px] border-primary bg-canvas py-6">
        <div class="container-site-sm flex items-center justify-between gap-4">
            <a href="/" aria-label="AAMEVi — Inicio">
                <x-brand-logo width="220px" />
            </a>

            <div class="flex items-center gap-5 text-xs">
                <a href="/" class="underline-offset-2 hover:underline hover:text-accent">Inicio</a>
                <x-preferences />
            </div>
        </div>
    </div>

    <main id="contenido" class="grow">
        <div class="container-site-sm flex justify-center py-10 md:py-16">
            <div class="w-full max-w-md">
                @yield('content')
            </div>
        </div>
    </main>

    <footer class="bg-ink py-4 text-center text-[11px] text-white/60">
        AAMEVi — Asociación Argentina de Medicina del Estilo de Vida
    </footer>
</body>
</html>
