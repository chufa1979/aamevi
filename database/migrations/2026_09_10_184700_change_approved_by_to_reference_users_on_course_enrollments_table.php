<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * `approved_by` deja de apuntar a `teachers` y pasa a apuntar a `users`.
 *
 * El perfil administrativo (`UserRole::Registrar`) no tiene ficha de
 * profesor, así que no podía quedar registrado como quien resolvió una
 * inscripción. De paso corrige un caso que ya estaba roto: un administrador
 * tampoco tiene ficha de profesor, así que hoy "Resuelta por" queda vacío
 * cuando aprueba él — con `users` como destino, todos los roles que pueden
 * aprobar quedan identificables.
 *
 * No hace falta tocar los valores ya guardados: `teachers.id` es la misma
 * clave que `users.id` (extensión 1:1), así que cada `approved_by` existente
 * sigue siendo válido contra la tabla nueva sin ningún UPDATE.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->foreign('approved_by')->references('id')->on('teachers')->nullOnDelete();
        });
    }
};
