<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Classroom\QuizController;
use App\Http\Controllers\Classroom\CourseController;
use App\Http\Controllers\Classroom\CatalogController;
use App\Http\Controllers\Classroom\ProgressController;
use App\Http\Controllers\Classroom\ClassroomController;
use App\Http\Controllers\Classroom\MyCoursesController;
use App\Http\Controllers\Classroom\SubmissionController;
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
     * El aula. Todo pasa por `student`, que resuelve la ficha del alumno: un
     * administrador o un docente no la tienen y no tienen nada que hacer acá.
     *
     * Las rutas son sustantivos en español porque son las que ve el alumno en
     * la barra de direcciones.
     */
    Route::middleware('student')->group(function () {
        Route::get('mis-cursos', [MyCoursesController::class, 'index'])->name('classroom.courses');
        Route::get('progreso', [ProgressController::class, 'index'])->name('classroom.progress');

        Route::get('cursos', [CatalogController::class, 'index'])->name('classroom.catalog');
        Route::post('cursos/{course}/inscripcion', [CatalogController::class, 'store'])->name('classroom.enroll');

        Route::get('cursos/{course}', [CourseController::class, 'show'])->name('classroom.course');
        Route::get('cursos/{course}/evaluaciones', [CourseController::class, 'evaluations'])->name('classroom.evaluations');

        Route::get('clases/{class}', [ClassroomController::class, 'show'])->name('classroom.class');
        Route::post('clases/{class}/completar', [ClassroomController::class, 'complete'])->name('classroom.class.complete');

        Route::post('tareas/{content}/entregar', [SubmissionController::class, 'store'])->name('classroom.submit');

        Route::get('evaluaciones/{quiz}', [QuizController::class, 'show'])->name('classroom.quiz');
        Route::post('evaluaciones/{quiz}', [QuizController::class, 'submit'])->name('classroom.quiz.submit');
    });

    /*
     * Secciones todavía sin módulo propio. Existen para que la navegación del
     * layout no apunte a 404; se van reemplazando por sus controladores reales
     * a medida que se implementan (ver docs/PLAN_ARQUITECTONICO.md).
     */
    $pendientes = [
        'certificados' => 'Certificados',
        'ayuda' => 'Ayuda',
        'buscar' => 'Buscar',
    ];

    foreach ($pendientes as $path => $title) {
        Route::get($path, fn () => view('placeholder', ['title' => $title]));
    }
});
