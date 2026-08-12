<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\CourseModuleFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Módulo de un curso. Se llama CourseModule y no Module porque `Module` es
 * demasiado genérico; la tabla sigue siendo `modules`, como en §2.
 */
class CourseModule extends Model
{
    /** @use HasFactory<CourseModuleFactory> */
    use HasFactory, HasUuids;

    protected $table = 'modules';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'order_number',
    ];

    protected function casts(): array
    {
        return [
            'order_number' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** Examen que evalúa el módulo entero. */
    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class, 'module_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(CourseClass::class, 'module_id')->orderBy('order_number');
    }
}
