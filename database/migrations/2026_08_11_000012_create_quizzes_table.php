<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Configuración de evaluación (docs/PLAN_ARQUITECTONICO.md §2, extendido).
 *
 * §2 definía el quiz solo por clase. Acá se agrega el **examen de módulo**, que
 * evalúa el conjunto: en lugar de un número fijo de preguntas, toma un
 * porcentaje del banco combinado de todas las clases del módulo. Con 5 clases de
 * 5 preguntas y un 40%, el examen sortea 10 de las 25.
 *
 * Por eso `class_id` y `module_id` son ambos nullable: un quiz cuelga de una
 * cosa o de la otra, nunca de las dos. Esa exclusividad la valida el modelo —
 * expresarla en el esquema exigiría un CHECK, que sqlite no admite agregar por
 * ALTER y dejaría los tests corriendo contra reglas distintas a producción.
 *
 * Los unique garantizan una sola evaluación por clase y una por módulo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('class_id')->nullable();
            $table->uuid('module_id')->nullable();
            $table->string('title')->nullable();

            // Cuántas preguntas ve cada alumno. En los quiz de clase es un
            // número; en los de módulo se calcula desde el porcentaje.
            $table->unsignedInteger('questions_per_student')->default(5);
            $table->unsignedTinyInteger('questions_percentage')->nullable();

            $table->unsignedTinyInteger('passing_score')->default(70);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->boolean('show_correct_answers')->default(true);
            $table->boolean('randomize_options')->default(true);
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('module_id')->references('id')->on('modules')->cascadeOnDelete();

            $table->unique('class_id');
            $table->unique('module_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
