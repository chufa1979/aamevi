<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Clases de un módulo (docs/PLAN_ARQUITECTONICO.md §2).
 *
 * `activation_date` es la fecha a partir de la cual la clase se ve; es la
 * columna sobre la que se arma el cronograma del curso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('module_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('order_number')->default(1);
            $table->timestamp('activation_date');
            $table->boolean('is_live_session')->default(false);
            $table->string('meet_link', 500)->nullable();
            $table->boolean('is_live_recording_available')->default(false);
            $table->timestamps();

            $table->foreign('module_id')->references('id')->on('modules')->cascadeOnDelete();
            $table->unique(['module_id', 'order_number']);
            $table->index('activation_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
