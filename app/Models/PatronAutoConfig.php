<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatronAutoConfig extends Model
{
    protected $fillable = ['enabled', 'amount', 'patron_type', 'updated_by'];

    protected $casts = ['enabled' => 'boolean', 'amount' => 'decimal:2'];

    // Single-row table — always the first row.
    public static function get(): self
    {
        return self::query()->first() ?? self::create([]);
    }
}
