<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameConfig extends Model
{
    protected $fillable = ['game_id', 'game_name', 'provider', 'settings'];

    protected $casts = ['settings' => 'array'];
}
