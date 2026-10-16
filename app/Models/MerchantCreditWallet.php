<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantCreditWallet extends Model
{
    protected $fillable = ['balance', 'last_reconciled_at', 'last_reconciled_transaction_id', 'reset_transaction_id', 'reset_at', 'reset_by'];

    protected $casts = [
        'balance'            => 'decimal:2',
        'last_reconciled_at' => 'datetime',
        'reset_at'           => 'datetime',
    ];

    // Single-row table by design — always row id 1.
    public static function current(): self
    {
        return self::query()->lockForUpdate()->first()
            ?? self::create(['balance' => 0]);
    }

    // Read-only variant for display (dashboard, etc.) — no row lock.
    public static function get(): self
    {
        return self::query()->first()
            ?? self::create(['balance' => 0]);
    }

    // Back-office reports only count transactions newer than the last
    // webmaster reset (see MerchantCreditService::reset()).
    public static function resetCutoff(): int
    {
        return (int) (self::query()->value('reset_transaction_id') ?? 0);
    }
}
