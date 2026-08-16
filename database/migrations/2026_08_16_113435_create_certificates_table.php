<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Certificados de finalización (docs/PLAN_ARQUITECTONICO.md §2).
 *
 * Cuelga de la inscripción y no del par alumno-curso porque la inscripción ya es
 * ese par, y además guarda cuándo cursó: dos ediciones del mismo curso son dos
 * inscripciones, y cada una merece su certificado.
 *
 * **Sin `pdf_url`**, a diferencia del DDL de §2. El certificado *es* estas cuatro
 * columnas; el PDF es una forma de mostrarlas, y se arma al descargarlo. Guardar
 * el archivo obligaría a regenerarlo cada vez que cambie un apellido mal escrito
 * o el diseño de la plantilla, y a limpiar los viejos. El número emitido, que es
 * lo que no se puede volver a calcular, sí queda guardado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('enrollment_id');

            // El número que figura impreso: es la referencia que alguien va a
            // citar por teléfono, así que se emite una vez y no se recalcula
            $table->string('certificate_number', 50)->unique();
            $table->timestamp('issued_at');

            $table->timestamps();

            $table->foreign('enrollment_id')->references('id')->on('course_enrollments')->cascadeOnDelete();

            // Un certificado por inscripción
            $table->unique('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
