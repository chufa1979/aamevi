<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#00b8b3">
    <link rel="icon" type="image/png" href="/favicon.png">
    <title>@yield('title', 'Aula') — AAMEVi</title>
    @include('partials.preferences-head')
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/preferences.js'])
</head>
{{--
    Layout del aula.

    Es una superficie aparte de `layouts.app`, como lo es `/admin`: el sitio
    institucional se navega con el menú horizontal y el aula con una barra
    lateral que cambia según el curso abierto.

    La estructura usa landmarks reales —`header`, `nav`, `main`, `aside`— para
    que un lector de pantalla pueda saltar entre zonas en vez de recorrer todo
    linealmente.
--}}
<body class="flex min-h-screen flex-col">
    <a href="#contenido" class="skip-link">Saltar al contenido</a>

    <x-top-bar />

    <header class="border-b-[6px] border-primary bg-canvas pb-5">
        <div class="container-site flex flex-wrap items-center justify-between gap-4">
            <a href="{{ route('classroom.courses') }}" class="block max-w-[240px]">
                <x-brand-logo />
            </a>

            <p class="text-sm uppercase tracking-wide text-subtle">Aula virtual</p>
        </div>
    </header>

    <div class="container-site flex grow flex-col gap-6 py-6 lg:flex-row lg:gap-10 lg:py-10">
        <x-classroom.nav :course="$cursoActual ?? null" />

        <main id="contenido" class="min-w-0 grow">
            @if (session('exito'))
                <p class="card mb-6 border-l-4 border-l-primary p-4 text-sm" role="status">
                    {{ session('exito') }}
                </p>
            @endif

            @if (session('error'))
                <p class="card mb-6 border-l-4 border-l-error p-4 text-sm" role="alert">
                    {{ session('error') }}
                </p>
            @endif

            @yield('content')
        </main>
    </div>

    <footer class="bg-ink py-4 text-center text-[11px] text-white/60">
        AAMEVi — Asociación Argentina de Medicina del Estilo de Vida
    </footer>
</body>
</html>
