<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Últimos tres campos para que la ficha pública iguale a la de referencia
 * (docs de la tarea: sección «Cuerpo docente», «Objetivos del curso» e
 * «Inscripción e Informes»).
 *
 * «Director del curso» no suma columna: ya es `courses.teacher_id`, y el
 * dato ampliado (bio, especialización) ya vive en `teachers` — el curso
 * tiene un solo responsable, por diseño (ver `Course::scopeVisibleTo`).
 * «Cuerpo docente» es sólo texto libre con otros nombres, no cuentas
 * adicionales: no rompe esa regla porque no le da a nadie más acceso al
 * curso, sólo aparece listado en la ficha pública.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->text('teaching_staff')->nullable()->after('certification_info');
            $table->text('objectives')->nullable()->after('teaching_staff');
            $table->text('enrollment_requirements')->nullable()->after('objectives');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['teaching_staff', 'objectives', 'enrollment_requirements']);
        });
    }
};
