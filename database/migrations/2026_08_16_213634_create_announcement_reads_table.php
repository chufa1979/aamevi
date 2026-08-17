<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Quién leyó cada comunicación.
 *
 * Tabla aparte y no una columna en `announcements` porque una comunicación es
 * para muchos: la dirigida a un alumno tiene un lector, la del curso tiene
 * tantos como inscriptos. Sin esto no se puede decir «tenés dos sin leer», que
 * es lo único que hace que alguien entre al tablón.
 *
 * Una fila **existe sólo si se leyó**: la ausencia es «sin leer». Precrearlas
 * para todos los inscriptos al publicar daría el mismo dato costando una fila
 * por alumno de entrada, y habría que mantenerlas al inscribirse alguien nuevo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('announcement_id');
            $table->uuid('student_id');

            $table->timestamp('read_at');

            $table->foreign('announcement_id')->references('id')->on('announcements')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();

            $table->unique(['announcement_id', 'student_id']);

            // La consulta del contador: lo leído por este alumno
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
    }
};
