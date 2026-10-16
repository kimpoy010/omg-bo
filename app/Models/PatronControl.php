<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatronControl extends Model
{
    public const TYPE_NURTURE = 14;
    public const TYPE_KILL    = 15;

    public const PATRON_TYPES = [0 => 'Fast', 1 => 'Slow', 2 => 'Ultimate'];

    protected $fillable = ['user_id', 'kind', 'patron_type', 'amount', 'status', 'set_by_user_id', 'cancelled_by_user_id', 'cancelled_at'];

    protected $casts = ['amount' => 'decimal:2', 'cancelled_at' => 'datetime'];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'user_id');
    }

    public function setBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by_user_id');
    }
}
