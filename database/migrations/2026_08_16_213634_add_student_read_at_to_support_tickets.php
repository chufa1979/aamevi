<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Hasta cuándo leyó el alumno su consulta.
 *
 * Acá alcanza con una columna, a diferencia de las comunicaciones: una consulta
 * tiene un solo lector de este lado. Es un timestamp y no un booleano porque el
 * hilo sigue: se compara contra la fecha del último mensaje, así que una
 * respuesta nueva vuelve a marcarla sin leer sin tener que tocar nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->timestamp('student_read_at')->nullable()->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn('student_read_at');
        });
    }
};
