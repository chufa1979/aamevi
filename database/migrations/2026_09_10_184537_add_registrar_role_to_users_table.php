<?php

use App\Enums\UserRole;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Suma `registrar` al ENUM de `users.role` (docs/PLAN_ARQUITECTONICO.md §2).
 *
 * `role` es un ENUM real —`$table->enum()` en la migración original—, no un
 * string libre: agregar un valor exige recrear la columna con la lista
 * completa, no alcanza con sumar el caso en `App\Enums\UserRole`. `change()`
 * lo resuelve sin SQL a medida por motor: en MySQL hace el `MODIFY COLUMN`,
 * en sqlite —donde corren los tests— reconstruye la tabla, porque ahí un
 * enum es un `varchar` con `CHECK`, no un tipo de columna aparte.
 *
 * El rollback falla si ya hay alguna cuenta con este rol: hay que
 * reasignarla antes de revertir, no hay un valor "de reemplazo" razonable
 * que elegir en su lugar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', UserRole::values())->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'teacher', 'student'])->change();
        });
    }
};
