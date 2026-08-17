<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * La marca de que un alumno leyó una comunicación.
 *
 * Sin `updated_at`: una lectura pasa una sola vez y no se corrige.
 */
class AnnouncementRead extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'announcement_id',
        'student_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
