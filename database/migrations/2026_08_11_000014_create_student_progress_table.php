<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Avance de cada alumno por las clases (docs/PLAN_ARQUITECTONICO.md §3-B).
 *
 * Es un registro derivado —se puede recalcular desde los intentos— pero se
 * persiste igual: la progresión se consulta en cada carga del aula y
 * reconstruirla cada vez implicaría recorrer todos los intentos del alumno.
 *
 * `completed_at` en null significa clase empezada pero no aprobada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('class_id');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();

            // Un solo registro de avance por alumno y clase
            $table->unique(['student_id', 'class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_progress');
    }
};
