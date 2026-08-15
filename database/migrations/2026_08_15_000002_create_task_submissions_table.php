<?php

use App\Enums\SubmissionStatus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Entregas de tareas (docs/PLAN_ARQUITECTONICO.md §13).
 *
 * Una fila por entrega y no una por par alumno-tarea: cuando el docente
 * desaprueba y el alumno vuelve a entregar, la corrección original tiene que
 * seguir existiendo. Es el mismo criterio que `student_quiz_attempts` y
 * `quiz_question_assignment` — una nota reclamada se tiene que poder
 * reconstruir.
 *
 * `published_at` separa corregir de publicar: el docente corrige cuando puede y
 * suelta las notas cuando terminó con toda la tanda, en lugar de que le vayan
 * apareciendo al alumno de a una.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('content_id');
            $table->uuid('student_id');
            $table->unsignedInteger('attempt_number')->default(1);

            $table->string('file_path', 500);
            $table->string('file_name');
            $table->timestamp('submitted_at');

            // 1 a 10 con un decimal: 7.5 es una nota corriente
            $table->decimal('grade', 4, 2)->nullable();
            $table->enum('status', SubmissionStatus::values())->default(SubmissionStatus::Pending->value);
            $table->text('feedback')->nullable();

            $table->uuid('graded_by')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->foreign('content_id')->references('id')->on('class_content')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            // Borrar al docente no puede borrar la corrección del alumno
            $table->foreign('graded_by')->references('id')->on('teachers')->nullOnDelete();

            $table->unique(['content_id', 'student_id', 'attempt_number']);
            $table->index(['content_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_submissions');
    }
};
