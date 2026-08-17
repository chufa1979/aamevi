<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Reseteos de intentos otorgados por el docente.
 *
 * Un alumno que agota los intentos de una autoevaluación queda trabado: la
 * progresión exige aprobarla. La única salida era subirle el límite a la
 * evaluación —lo cual se lo sube a todo el curso— o borrar el intento, que
 * destruye la prueba de sobre qué se lo calificó.
 *
 * **No se borra ni se pisa nada.** Cada fila abre un ciclo nuevo: el límite pasa
 * a contarse desde el último reseteo, y el historial completo queda. Encima
 * queda registrado quién destrabó a quién y por qué, que es justo lo que se
 * pierde cuando alguien mueve un número global.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempt_resets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('quiz_id');
            $table->uuid('student_id');

            // Quién lo otorgó. Se conserva el reseteo aunque se borre la cuenta:
            // el alumno rindió de nuevo gracias a él y eso tiene que constar
            $table->uuid('granted_by')->nullable();

            $table->text('reason')->nullable();

            $table->timestamp('created_at');

            $table->foreign('quiz_id')->references('id')->on('quizzes')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('granted_by')->references('id')->on('users')->nullOnDelete();

            // La consulta del limite: el ultimo reseteo de este par
            $table->index(['quiz_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_resets');
    }
};
