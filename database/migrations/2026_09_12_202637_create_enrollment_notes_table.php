<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Bitácora del perfil administrativo sobre una solicitud de inscripción.
 *
 * Un pago por transferencia o Mercado Pago no deja rastro en la plataforma: la
 * confirmación llega por WhatsApp, por teléfono o de palabra, y hasta ahora no
 * había dónde dejarla escrita. Es interna —el alumno nunca la ve— y por eso no
 * pasa por `email_queue` ni por `NotificationService`.
 *
 * Sin `updated_at`, mismo criterio que `quiz_attempt_resets`: un asiento de
 * bitácora se escribe una vez y no se corrige. Si algo cambió, se agrega una
 * fila nueva.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('enrollment_id');

            // Se conserva la nota aunque se borre la cuenta de quien la escribió
            $table->uuid('author_id')->nullable();

            $table->text('body');

            $table->timestamp('created_at');

            $table->foreign('enrollment_id')->references('id')->on('course_enrollments')->cascadeOnDelete();
            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['enrollment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_notes');
    }
};
