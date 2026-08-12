<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'class_id',
        'text',
        'question_type',
        'is_active',
        'created_by',
        'order_number',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order_number' => 'integer',
        ];
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class, 'class_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order_number');
    }

    public function correctOption(): ?QuestionOption
    {
        return $this->options->firstWhere('is_correct', true);
    }
}
