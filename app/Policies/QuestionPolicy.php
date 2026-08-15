<?php

namespace App\Policies;

use App\Models\Course;
use Illuminate\Database\Eloquent\Model;

class QuestionPolicy extends CoursePartPolicy
{
    protected function courseOf(Model $record): ?Course
    {
        return $record->class?->module?->course;
    }
}
