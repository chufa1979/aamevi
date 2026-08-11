<?php

use App\Enums\UserRole;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Tabla base de autenticación (docs/PLAN_ARQUITECTONICO.md §2).
 *
 * Se adapta el nombre de tres columnas respecto del DDL del plan, para poder
 * usar la maquinaria que Laravel ya trae en lugar de reimplementarla:
 *
 *   password_hash            -> password           (Auth::attempt)
 *   email_verified (bool)    -> email_verified_at  (MustVerifyEmail)
 *   email_verification_token -> se elimina; Laravel usa URLs firmadas
 *
 * Y se agrega `remember_token`, que el plan no contemplaba y que hace falta
 * para el "recordarme" del login.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();

            // Nullable: las cuentas creadas por Google OAuth no tienen contraseña
            $table->string('password')->nullable();

            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->enum('role', UserRole::values());
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();

            $table->string('oauth_provider', 50)->nullable();
            $table->string('oauth_id')->nullable();

            $table->rememberToken();
            $table->timestamps();

            // Evita que dos cuentas se asocien a la misma identidad de Google
            $table->unique(['oauth_provider', 'oauth_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
