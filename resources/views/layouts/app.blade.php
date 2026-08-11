<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AAMEVi - Plataforma de formación en medicina del estilo de vida">
    <meta name="theme-color" content="#00b8b3">
    <link rel="icon" type="image/png" href="/favicon.png">
    <title>@yield('title', 'AAMEVi - Educación')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{--
    Estructura general de página del sitio madre: `.wrapper` en columna con el
    `<main>` creciendo para empujar el pie al fondo de la ventana.
--}}
<body class="flex min-h-screen flex-col">
    <x-top-bar />
    <x-header />

    <main class="grow">
        @yield('content')
    </main>

    <x-footer />
</body>
</html>
