<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Fecha de entrega de una tarea.
 *
 * Va en `class_content` y no en una tabla aparte porque la tarea *es* un ítem de
 * contenido: inventarle una tabla de configuración para una sola fecha sería
 * peor. Es nullable y sólo tiene sentido en los de tipo `task` — una tarea sin
 * fecha se puede entregar siempre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_content', function (Blueprint $table) {
            $table->timestamp('due_date')->nullable()->after('content_url');
        });
    }

    public function down(): void
    {
        Schema::table('class_content', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }
};
