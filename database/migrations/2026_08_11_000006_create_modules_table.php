<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Módulos de un curso (docs/PLAN_ARQUITECTONICO.md §2).
 *
 * `order_number` define la secuencia dentro del curso; es único por curso para
 * que no queden dos módulos disputándose la misma posición.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('order_number')->default(1);
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->unique(['course_id', 'order_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
