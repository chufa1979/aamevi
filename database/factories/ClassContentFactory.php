<?php

namespace Database\Factories;

use App\Models\CourseClass;
use App\Models\ClassContent;
use App\Enums\ClassContentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassContent>
 */
class ClassContentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'class_id' => CourseClass::factory(),
            'type' => ClassContentType::Text,
            'title' => fake()->sentence(3),
            'description' => fake()->paragraphs(2, true),
            'content_url' => null,
            'order_number' => fake()->unique()->numberBetween(1, 1000),
        ];
    }

    public function video(): static
    {
        return $this->state([
            'type' => ClassContentType::Video,
            'description' => null,
            'content_url' => 'https://www.youtube.com/watch?v='.fake()->lexify('???????????'),
        ]);
    }

    /** PDF subido al disco público: `content_url` guarda la ruta relativa. */
    public function pdf(): static
    {
        return $this->state([
            'type' => ClassContentType::Pdf,
            'description' => null,
            'content_url' => 'class-content/'.fake()->uuid().'.pdf',
        ]);
    }

    public function task(): static
    {
        return $this->state([
            'type' => ClassContentType::Task,
            'content_url' => null,
        ]);
    }
}
