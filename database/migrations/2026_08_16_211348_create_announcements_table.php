<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Comunicaciones del curso (docs/PLAN_ARQUITECTONICO.md §13).
 *
 * Tablón de anuncios, no mensajería: escribe el docente y el alumno lee. Si
 * el alumno necesita preguntar algo, para eso están las consultas.
 *
 * `student_id` en null significa «para todo el curso». Es la forma de FID y se
 * conserva porque el caso frecuente —avisar que se corrió una clase— es el
 * general, y obligar a elegir destinatario cada vez sería fricción por el caso
 * raro.
 *
 * **Sin borradores.** Una comunicación se escribe cuando hay algo que decir; la
 * pantalla de edición existe para corregir una errata, no para preparar textos.
 * `notified_at` guarda si además salió por correo, que es la decisión que sí se
 * toma al publicar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id');

            // Null: para todo el curso
            $table->uuid('student_id')->nullable();

            // Quién la firma. Se conserva la comunicación aunque se borre la
            // cuenta: el alumno la leyó y tiene que seguir estando
            $table->uuid('author_id')->nullable();

            $table->string('title');
            $table->text('body');

            $table->timestamp('published_at');
            $table->timestamp('notified_at')->nullable();

            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['course_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
