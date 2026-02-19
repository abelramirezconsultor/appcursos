<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivationCodeRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'activation_code_id',
        'user_id',
        'ip_address',
        'user_agent',
        'redeemed_at',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
    ];

    public function activationCode(): BelongsTo
    {
        return $this->belongsTo(ActivationCode::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
