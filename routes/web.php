<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Classroom\QuizController;
use App\Http\Controllers\Classroom\CourseController;
use App\Http\Controllers\Classroom\SearchController;
use App\Http\Controllers\Classroom\CatalogController;
use App\Http\Controllers\Classroom\ProgressController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Classroom\ClassroomController;
use App\Http\Controllers\Classroom\MyCoursesController;
use App\Http\Controllers\Classroom\SubmissionController;
use App\Http\Controllers\Classroom\CertificateController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

/*
 * La plataforma es privada: nada es accesible sin sesión iniciada. Quien no
 * está autenticado solo ve el login, y el middleware `auth` lo redirige ahí
 * desde cualquier otra ruta.
 */

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('registro', [RegisteredUserController::class, 'create'])->name('register');

    // Limitado: el alta es pública, y sin freno es un formulario para llenar la
    // tabla de usuarios desde un script
    Route::post('registro', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => view('home'))->name('home');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    /*
     * Verificación del correo. Va dentro de `auth` y fuera de `verified`, que es
     * justamente lo único que puede hacer quien todavía no verificó.
     */
    Route::get('verificar-email', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');

    Route::get('verificar-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('verificar-email/reenviar', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    /*
     * El aula. Todo pasa por `student`, que resuelve la ficha del alumno: un
     * administrador o un docente no la tienen y no tienen nada que hacer acá.
     *
     * Las rutas son sustantivos en español porque son las que ve el alumno en
     * la barra de direcciones.
     */
    /*
     * El aula pide además correo verificado. Registrarse no alcanza: sin ese
     * paso, cualquiera podría anotarse con la dirección de otro y quedar
     * cursando a su nombre.
     */
    Route::middleware(['student', 'verified'])->group(function () {
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

        Route::get('certificados', [CertificateController::class, 'index'])->name('classroom.certificates');
        Route::get('certificados/{certificate}', [CertificateController::class, 'download'])->name('classroom.certificate');

        Route::get('buscar', [SearchController::class, 'index'])->name('classroom.search');
    });

    /*
     * Secciones todavía sin módulo propio. Existen para que la navegación del
     * layout no apunte a 404; se van reemplazando por sus controladores reales
     * a medida que se implementan (ver docs/PLAN_ARQUITECTONICO.md).
     */
    $pendientes = [
        'ayuda' => 'Ayuda',
    ];

    foreach ($pendientes as $path => $title) {
        Route::get($path, fn () => view('placeholder', ['title' => $title]));
    }
});
