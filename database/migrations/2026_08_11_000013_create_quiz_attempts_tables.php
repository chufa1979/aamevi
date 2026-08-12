<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Intentos de evaluación (docs/PLAN_ARQUITECTONICO.md §2).
 *
 * `quiz_question_assignment` es la pieza que hace auditable el sistema: registra
 * qué preguntas le tocaron a cada alumno en cada intento. Sin ella, ante una
 * nota reclamada no hay forma de reconstruir sobre qué se calificó, porque el
 * sorteo es distinto para cada uno.
 *
 * Desviación respecto de §2: se elimina `is_passed_threshold`. El DDL tenía esa
 * columna junto a `passed`, ambas booleanas y con el mismo significado; dos
 * fuentes para el mismo dato terminan discrepando.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_quiz_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('quiz_id');
            $table->uuid('student_id');
            $table->unsignedInteger('attempt_number')->default(1);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->boolean('passed')->nullable();
            $table->timestamps();

            $table->foreign('quiz_id')->references('id')->on('quizzes')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();

            // Impide que dos envíos simultáneos generen el mismo número de intento
            $table->unique(['quiz_id', 'student_id', 'attempt_number']);
        });

        Schema::create('quiz_question_assignment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('attempt_id');
            $table->uuid('question_id');
            $table->unsignedInteger('assigned_order');
            $table->timestamps();

            $table->foreign('attempt_id')->references('id')->on('student_quiz_attempts')->cascadeOnDelete();
            // Las preguntas no se borran mientras existan intentos que las usaron:
            // hacerlo dejaría intentos imposibles de reconstruir.
            $table->foreign('question_id')->references('id')->on('questions')->restrictOnDelete();

            $table->unique(['attempt_id', 'question_id']);
        });

        Schema::create('student_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('attempt_id');
            $table->uuid('question_id');
            $table->uuid('selected_option_id')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamp('answered_at')->useCurrent();
            $table->timestamps();

            $table->foreign('attempt_id')->references('id')->on('student_quiz_attempts')->cascadeOnDelete();
            $table->foreign('question_id')->references('id')->on('questions')->restrictOnDelete();
            $table->foreign('selected_option_id')->references('id')->on('question_options')->nullOnDelete();

            // Una respuesta por pregunta y por intento
            $table->unique(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_answers');
        Schema::dropIfExists('quiz_question_assignment');
        Schema::dropIfExists('student_quiz_attempts');
    }
};
