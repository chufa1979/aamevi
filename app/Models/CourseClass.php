<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\CourseClassFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Clase dentro de un módulo. Se llama CourseClass porque `Class` es palabra
 * reservada de PHP; la tabla sigue siendo `classes`, como en §2.
 */
class CourseClass extends Model
{
    /** @use HasFactory<CourseClassFactory> */
    use HasFactory, HasUuids;

    protected $table = 'classes';

    protected $fillable = [
        'module_id',
        'title',
        'description',
        'order_number',
        'activation_date',
        'is_live_session',
        'meet_link',
        'is_live_recording_available',
    ];

    protected function casts(): array
    {
        return [
            'activation_date' => 'datetime',
            'is_live_session' => 'boolean',
            'is_live_recording_available' => 'boolean',
            'order_number' => 'integer',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class, 'class_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'class_id')->orderBy('order_number');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(ClassContent::class, 'class_id')->orderBy('order_number');
    }

    /** Una clase está disponible cuando llegó su fecha de activación. */
    public function isAvailable(): bool
    {
        return $this->activation_date->isPast();
    }

    /**
     * Cuelga de la del curso: ver Course::scopeVisibleTo().
     *
     * @param  Builder<CourseClass>  $query
     * @return Builder<CourseClass>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('module', fn (Builder $q): Builder => $q->visibleTo($user));
    }
}
