<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackOfficeOperationLog extends Model
{
    // Laravel would guess back_office_*; the migrations use backoffice_*.
    protected $table = 'backoffice_operation_logs';

    public $timestamps = false;

    protected $fillable = ['backoffice_admin_id', 'action', 'description', 'ip_address', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'backoffice_admin_id');
    }

    public static function record(string $action, ?string $description = null): void
    {
        self::create([
            'backoffice_admin_id' => \Illuminate\Support\Facades\Auth::id(),
            'action'               => $action,
            'description'          => $description,
            'ip_address'           => request()?->ip(),
            'created_at'           => now(),
        ]);
    }
}
