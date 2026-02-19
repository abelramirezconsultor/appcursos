<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentLessonProgress extends Model
{
    use HasFactory;

    protected $table = 'enrollment_lesson_progress';

    protected $fillable = [
        'enrollment_id',
        'lesson_id',
        'progress_percent',
        'viewed_at',
        'completed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrollment::class, 'enrollment_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }
}
