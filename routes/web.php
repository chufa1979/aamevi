<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'));

/*
 * Secciones todavía sin módulo propio. Existen para que la navegación del
 * layout no apunte a 404; se van reemplazando por sus controladores reales
 * a medida que se implementan (ver docs/PLAN_ARQUITECTONICO.md).
 */
$pendientes = [
    'cursos' => 'Cursos',
    'mis-cursos' => 'Mis cursos',
    'progreso' => 'Mi progreso',
    'certificados' => 'Certificados',
    'ayuda' => 'Ayuda',
    'login' => 'Iniciar sesión',
    'registro' => 'Crear cuenta',
    'buscar' => 'Buscar',
];

foreach ($pendientes as $path => $title) {
    Route::get($path, fn () => view('placeholder', ['title' => $title]));
}
