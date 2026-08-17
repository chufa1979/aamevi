<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Los mensajes de una consulta.
 *
 * Tabla aparte y no una columna `respuesta` en el ticket: en FID el hilo era de
 * una sola respuesta, y cuando hacía falta repreguntar no quedaba dónde. Una
 * consulta es una conversación, aunque casi siempre sea corta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');

            // Se conserva el mensaje aunque se borre la cuenta: sin él, el hilo
            // queda con huecos y deja de entenderse
            $table->uuid('author_id')->nullable();

            $table->text('body');

            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('support_tickets')->cascadeOnDelete();
            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};
