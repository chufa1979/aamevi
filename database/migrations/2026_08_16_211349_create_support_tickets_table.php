<?php

use App\Enums\TicketStatus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Consultas a mesa de ayuda (docs/PLAN_ARQUITECTONICO.md §13).
 *
 * **La consulta cuelga del curso**, a diferencia de FID, donde el listado vivía
 * dentro del menú del curso pero no filtraba por él. La atiende quien dicta ese
 * curso: una duda sobre una clase no tiene por qué pasar por gente que no la
 * dictó. La administración las ve todas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id');
            $table->uuid('student_id');

            $table->string('subject');
            $table->enum('status', TicketStatus::values())->default(TicketStatus::Open->value);
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();

            $table->index(['course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
