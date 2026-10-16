<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OmgTransaction extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'type', 'money', 'balance_after',
        'game_id', 'session_id', 'round_id', 'cancel_order_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'user_id');
    }
}
