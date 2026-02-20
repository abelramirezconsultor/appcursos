<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CourseModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'position',
        'prerequisite_module_id',
        'is_prerequisite_mandatory',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class, 'module_id');
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class, 'module_id');
    }

    public function prerequisiteModule(): BelongsTo
    {
        return $this->belongsTo(self::class, 'prerequisite_module_id');
    }

    public function dependentModules(): HasMany
    {
        return $this->hasMany(self::class, 'prerequisite_module_id');
    }
}
