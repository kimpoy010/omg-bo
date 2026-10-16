<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantCallbackUrl extends Model
{
    // field => label, in the order OMG calls them.
    public const FIELDS = [
        'verify_session_url' => 'Verify Session URL',
        'get_balance_url'    => 'Get Balance URL',
        'change_balance_url' => 'Change Balance URL',
    ];

    protected $fillable = ['verify_session_url', 'get_balance_url', 'change_balance_url', 'updated_by'];

    // Single-row table — always the first row.
    public static function get(): self
    {
        return self::query()->first() ?? self::create([]);
    }
}
