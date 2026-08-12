<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Extensión 1:1 de `users` con los datos propios del profesor
 * (docs/PLAN_ARQUITECTONICO.md §2).
 *
 * Igual que `students`: la PK es la FK a users.id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->text('bio')->nullable();
            $table->string('specialization')->nullable();

            $table->timestamps();

            $table->foreign('id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
