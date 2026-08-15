<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\ClassContent;
use App\Models\TaskSubmission;
use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskSubmission>
 */
class TaskSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'content_id' => ClassContent::factory()->task(),
            'student_id' => Student::factory(),
            'attempt_number' => 1,
            'file_path' => 'submissions/'.fake()->uuid().'.pdf',
            'file_name' => 'trabajo-practico.pdf',
            'submitted_at' => now()->subDays(2),
            'status' => SubmissionStatus::Pending,
        ];
    }

    /** Corregida y aprobada, pero sin publicar: el alumno todavía no la ve. */
    public function approved(float $nota = 8): static
    {
        return $this->state([
            'status' => SubmissionStatus::Approved,
            'grade' => $nota,
            'feedback' => 'Buen trabajo.',
            'graded_by' => Teacher::factory(),
            'graded_at' => now()->subDay(),
        ]);
    }

    public function rejected(float $nota = 4): static
    {
        return $this->state([
            'status' => SubmissionStatus::Rejected,
            'grade' => $nota,
            'feedback' => 'Faltó desarrollar la consigna.',
            'graded_by' => Teacher::factory(),
            'graded_at' => now()->subDay(),
        ]);
    }

    public function published(): static
    {
        return $this->state(['published_at' => now()]);
    }
}
