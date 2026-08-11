<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\Student;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'student_id' => Student::factory(),
            'attempt_number' => 1,
            'started_at' => now(),
        ];
    }

    public function submitted(int $score = 80): static
    {
        return $this->state(fn (array $attributes): array => [
            'submitted_at' => now(),
            'score' => $score,
            'passed' => $score >= 70,
        ]);
    }
}
