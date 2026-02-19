<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'assigned_user_id',
        'code',
        'expires_at',
        'max_uses',
        'uses_count',
        'is_active',
        'revoked_at',
        'created_by',
        'meta',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(ActivationCodeRedemption::class);
    }
}
