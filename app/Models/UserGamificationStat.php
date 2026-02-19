<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGamificationStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'xp_total',
        'level',
        'streak_days',
        'last_activity_date',
        'lessons_completed',
    ];

    protected $casts = [
        'last_activity_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
