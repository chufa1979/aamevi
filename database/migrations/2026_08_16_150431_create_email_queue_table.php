<?php

use App\Enums\EmailType;
use App\Enums\EmailStatus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * La cola de avisos (docs/PLAN_ARQUITECTONICO.md §2).
 *
 * **Por qué una tabla y no la cola de Laravel.** El hosting es compartido: no
 * hay supervisor ni forma de dejar un `queue:work` corriendo. Lo que sí hay es
 * cron, y con cron la opción es una tabla que un comando vacía cada tantos
 * minutos. Además esta tabla se puede mirar: la pregunta que llega de la
 * administración es «¿le llegó el mail a fulano?», y en `jobs` esa respuesta
 * está adentro de un payload serializado.
 *
 * **El asunto y el cuerpo se guardan ya armados.** Así lo que quedó registrado
 * es exactamente lo que se mandó, y cambiar mañana una plantilla no reescribe
 * la historia.
 *
 * `last_error` no está en §2: sin él, un aviso fallido no dice por qué, y la
 * única salida sería reintentar a ciegas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_queue', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('recipient_id');

            $table->enum('email_type', EmailType::values());
            $table->string('subject');
            $table->text('body');

            // Separar «cuándo se creó» de «cuándo corresponde mandarlo» es lo
            // que permite programar el recordatorio de una clase con un día de
            // anticipación sin tener que esperar despierto hasta esa hora
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();

            $table->enum('status', EmailStatus::values())->default(EmailStatus::Pending->value);
            $table->unsignedInteger('retry_count')->default(0);
            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->foreign('recipient_id')->references('id')->on('users')->cascadeOnDelete();

            // La consulta del worker: pendientes cuya hora ya llegó
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_queue');
    }
};
