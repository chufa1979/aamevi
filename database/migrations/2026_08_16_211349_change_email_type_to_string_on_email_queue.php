<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * `email_type` deja de ser un ENUM del esquema.
 *
 * La lista de avisos vive en `App\Enums\EmailType`, que es donde se lee y donde
 * se agrega uno nuevo. Mantenerla también en la base obliga a una migración que
 * reescribe la columna cada vez que aparece un tipo de aviso —dos en este mismo
 * cambio, comunicación y respuesta de consulta—, y el ENUM de MySQL además
 * bloquea la tabla para hacerlo.
 *
 * El cast del modelo sigue validando: un valor que no esté en el enum de PHP
 * rompe al leerlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_queue', function (Blueprint $table) {
            $table->string('email_type', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('email_queue', function (Blueprint $table) {
            $table->enum('email_type', [
                'verification',
                'enrollment_approved',
                'class_reminder',
                'certificate',
                'task_graded',
            ])->change();
        });
    }
};
