<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameConfigChangeRequest extends Model
{
    protected $fillable = [
        'backoffice_admin_id', 'game_id', 'game_name', 'provider', 'field',
        'previous_value', 'new_value', 'status', 'applied_by', 'applied_at',
    ];

    protected $casts = ['applied_at' => 'datetime'];

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'backoffice_admin_id');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }
}
