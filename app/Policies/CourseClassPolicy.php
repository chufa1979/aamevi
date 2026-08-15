<?php

namespace App\Policies;

use App\Models\Course;
use Illuminate\Database\Eloquent\Model;

class CourseClassPolicy extends CoursePartPolicy
{
    protected function courseOf(Model $record): ?Course
    {
        return $record->module?->course;
    }
}
