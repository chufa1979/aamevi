<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Cursos (docs/PLAN_ARQUITECTONICO.md §2).
 *
 * El docente se referencia contra `teachers`, no contra `users`: solo alguien
 * con ficha de profesor puede dictar un curso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->uuid('teacher_id');
            $table->unsignedInteger('max_students')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('teachers')->cascadeOnDelete();
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
