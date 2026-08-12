<?php

use App\Enums\ClassContentType;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Contenido de una clase (docs/PLAN_ARQUITECTONICO.md §2).
 *
 * `content_url` guarda el archivo o el enlace en los tipos que lo usan (video y
 * pdf). En los tipos `text` y `task` el cuerpo va en `description`, tal como
 * define el DDL del plan: no hay una columna aparte para el texto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_content', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('class_id');
            $table->enum('type', ClassContentType::values());
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('content_url', 500)->nullable();
            $table->unsignedInteger('order_number')->default(1);
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->index(['class_id', 'order_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_content');
    }
};
