<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// A casino player account, as known to the merchant back office. These are
// data only — players never sign in to this app. Rows are created by whatever
// feeds omg_transactions (the casino's OMG callback, an import, or the demo
// seeder); `id` is the same id OMG sees as the player's uname/user id.
class Player extends Model
{
    protected $fillable = ['username', 'last_seen_at'];

    protected $casts = ['last_seen_at' => 'datetime'];

    public function omgTransactions(): HasMany
    {
        return $this->hasMany(OmgTransaction::class, 'user_id');
    }
}
