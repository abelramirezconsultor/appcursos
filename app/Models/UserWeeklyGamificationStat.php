<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWeeklyGamificationStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'week_start',
        'xp_earned',
        'lessons_completed',
    ];

    protected $casts = [
        'week_start' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
