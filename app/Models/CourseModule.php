<?php

namespace App\Models;

use Carbon\Carbon;
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

    /**
     * Fecha más temprana que puede tener una clase en la posición `$orden`.
     *
     * Es la fecha de la última clase que va antes que ella: el cronograma
     * avanza en el tiempo, así que una clase no puede habilitarse antes que la
     * que la precede. El mismo día sí, por eso se compara por día y no por hora
     * —dos clases del mismo día son normales—.
     *
     * Devuelve null cuando no hay ninguna clase antes, que es el caso de la
     * primera del módulo: esa puede ir cuando se quiera.
     */
    public function earliestDateFor(int $orden): ?Carbon
    {
        $ultima = $this->classes()
            ->where('order_number', '<', $orden)
            ->max('activation_date');

        return $ultima === null ? null : Carbon::parse($ultima)->startOfDay();
    }

    /** La posición que le tocaría a una clase nueva: al final del módulo. */
    public function nextClassOrder(): int
    {
        return ((int) $this->classes()->max('order_number')) + 1;
    }
}
