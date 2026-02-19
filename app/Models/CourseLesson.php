<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'title',
        'video_url',
        'duration_seconds',
        'position',
        'xp_reward',
        'is_preview',
    ];

    protected $casts = [
        'is_preview' => 'boolean',
        'duration_seconds' => 'integer',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function enrollmentProgress(): HasMany
    {
        return $this->hasMany(EnrollmentLessonProgress::class, 'lesson_id');
    }
}
