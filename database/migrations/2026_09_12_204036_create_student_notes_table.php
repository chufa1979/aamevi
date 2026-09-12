<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Bitácora del perfil administrativo sobre un alumno.
 *
 * Cuelga del alumno y no de una inscripción puntual: un pago confirmado por
 * transferencia o Mercado Pago, o una aclaración de cuenta, no son cosas de un
 * curso en particular. Es interna —el alumno nunca la ve— y por eso no pasa
 * por `email_queue` ni por `NotificationService`.
 *
 * Sin `updated_at`, mismo criterio que `quiz_attempt_resets`: un asiento de
 * bitácora se escribe una vez y no se corrige. Si algo cambió, se agrega una
 * fila nueva.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');

            // Se conserva la nota aunque se borre la cuenta de quien la escribió
            $table->uuid('author_id')->nullable();

            $table->text('body');

            $table->timestamp('created_at');

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['student_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_notes');
    }
};
