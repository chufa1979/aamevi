<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Extensión 1:1 de `users` con los datos propios del alumno
 * (docs/PLAN_ARQUITECTONICO.md §2).
 *
 * La PK es también la FK a users.id: no lleva id propio. Borrar el usuario
 * arrastra su ficha de alumno.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('dni', 20)->nullable()->unique();
            $table->date('date_of_birth')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('cell_phone', 20)->nullable();
            $table->string('sub_delegation', 100)->nullable();
            $table->string('delegation', 100)->nullable();

            $table->timestamps();

            $table->foreign('id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
