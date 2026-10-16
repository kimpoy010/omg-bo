<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OmgRtpSetting extends Model
{
    protected $fillable = ['user_id', 'switch', 'control_type', 'rtp', 'set_by_user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'user_id');
    }

    public function setBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by_user_id');
    }

    public function isActive(): bool
    {
        return $this->switch === 2;
    }

    public function rtpPercent(): string
    {
        return \App\Services\OmgApiService::rtpOptions()[$this->rtp] ?? ($this->rtp / 100 . '%');
    }
}
