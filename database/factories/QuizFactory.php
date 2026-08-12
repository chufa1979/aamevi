<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\CourseClass;
use App\Models\CourseModule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'class_id' => CourseClass::factory(),
            'module_id' => null,
            'title' => 'Evaluación',
            'questions_per_student' => 5,
            'passing_score' => 70,
            'max_attempts' => 3,
        ];
    }

    /** Examen de módulo: toma un porcentaje del banco combinado. */
    public function forModule(?CourseModule $module = null, int $percentage = 40): static
    {
        return $this->state([
            'class_id' => null,
            'module_id' => $module?->getKey() ?? CourseModule::factory(),
            'title' => 'Examen del módulo',
            'questions_percentage' => $percentage,
        ]);
    }
}
