<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'student_id' => null,
            'author_id' => null,
            'title' => 'Cambio en el cronograma',
            'body' => '<p>La clase del jueves se corre al martes siguiente.</p>',
            'published_at' => now(),
        ];
    }

    public function notified(): static
    {
        return $this->state(['notified_at' => now()]);
    }
}
