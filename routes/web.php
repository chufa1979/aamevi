<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

/*
 * La plataforma es privada: nada es accesible sin sesión iniciada. Quien no
 * está autenticado solo ve el login, y el middleware `auth` lo redirige ahí
 * desde cualquier otra ruta.
 */

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::view('registro', 'auth.register')->name('register');
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => view('home'))->name('home');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

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
        'buscar' => 'Buscar',
    ];

    foreach ($pendientes as $path => $title) {
        Route::get($path, fn () => view('placeholder', ['title' => $title]));
    }
});
