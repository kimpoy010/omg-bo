<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantCreditLog extends Model
{
    protected $fillable = ['type', 'amount', 'balance_after', 'backoffice_admin_id', 'webmaster_id', 'description'];

    protected $casts = [
        'amount'        => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function webmaster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'webmaster_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'backoffice_admin_id');
    }
}
