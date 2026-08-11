<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Opciones de respuesta (docs/PLAN_ARQUITECTONICO.md §2).
 *
 * Que haya exactamente una correcta se valida en el formulario, no en el
 * esquema: expresarlo como restricción de base exigiría un trigger, y dejaría
 * la tabla imposible de poblar paso a paso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('question_id');
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('order_number')->default(1);
            $table->timestamps();

            $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
            $table->index(['question_id', 'order_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};
