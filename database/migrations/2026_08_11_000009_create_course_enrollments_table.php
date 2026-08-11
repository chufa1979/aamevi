<?php

use App\Enums\EnrollmentStatus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Inscripciones a un curso (docs/PLAN_ARQUITECTONICO.md §2).
 *
 * El unique (course_id, student_id) es lo que impide que un alumno se inscriba
 * dos veces al mismo curso, incluso si el pedido llega repetido.
 *
 * `approved_by` no borra en cascada: si se elimina al docente que aprobó, la
 * inscripción tiene que sobrevivir con el dato en null. Perder inscripciones
 * porque cambió el profesor sería inaceptable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id');
            $table->uuid('student_id');
            $table->timestamp('enrollment_date')->useCurrent();
            $table->enum('status', EnrollmentStatus::values())->default(EnrollmentStatus::Pending->value);
            $table->timestamp('approval_date')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('teachers')->nullOnDelete();

            $table->unique(['course_id', 'student_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');
    }
};
