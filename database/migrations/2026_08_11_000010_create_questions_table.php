<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Banco de preguntas (docs/PLAN_ARQUITECTONICO.md §2).
 *
 * Las preguntas pertenecen siempre a una clase. El examen del módulo no tiene
 * banco propio: toma un porcentaje de las preguntas de las clases que lo
 * componen.
 *
 * Desviación respecto de §2: `created_by` es nullable. El DDL lo declaraba
 * obligatorio contra `teachers`, pero las preguntas también las carga
 * administración, que no tiene ficha de profesor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('class_id');
            $table->text('text');
            $table->string('question_type')->default('multiple_choice');
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->unsignedInteger('order_number')->default(1);
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('teachers')->nullOnDelete();

            $table->index(['class_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
