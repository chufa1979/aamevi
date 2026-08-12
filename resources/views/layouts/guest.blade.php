<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#00b8b3">
    <link rel="icon" type="image/png" href="/favicon.png">
    <title>@yield('title', 'Acceso') — AAMEVi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{--
    Layout de las pantallas de acceso. A diferencia de `layouts.app`, no incluye
    navegación ni pie con menú: quien no inició sesión no debe ver la estructura
    de la plataforma.
--}}
<body class="flex min-h-screen flex-col">
    <div class="border-b-[6px] border-primary bg-surface py-6">
        <div class="container-site-sm flex justify-center">
            <img src="/images/aamevi.svg" alt="AAMEVi" class="w-[220px] max-w-full">
        </div>
    </div>

    <main class="grow">
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
