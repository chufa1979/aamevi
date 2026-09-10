<?php

use App\Enums\CourseModality;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Campos de la ficha pública del curso (docs/PLAN_ARQUITECTONICO.md no los
 * define todavía: se suman para que el CRUD de admin pueda cargar el mismo
 * nivel de detalle que la ficha de referencia — ver `resources/views/public/
 * course.blade.php`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('modality')->default(CourseModality::Online->value)->after('end_date');
            $table->string('location')->nullable()->after('modality');
            $table->string('specialties')->nullable()->after('location');
            $table->string('schedule_days')->nullable()->after('specialties');
            $table->string('schedule_time')->nullable()->after('schedule_days');
            $table->text('investment_info')->nullable()->after('schedule_time');
            $table->text('certification_info')->nullable()->after('investment_info');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'modality',
                'location',
                'specialties',
                'schedule_days',
                'schedule_time',
                'investment_info',
                'certification_info',
            ]);
        });
    }
};
