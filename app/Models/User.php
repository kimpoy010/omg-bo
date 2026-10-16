<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

// Staff accounts for this app. Exactly two roles exist:
//   webmaster — loads merchant credit, applies game-config change requests,
//               manages the merchant's login.
//   merchant  — the OMG merchant account holder; uses the /backoffice section.
#[Fillable(['name', 'username', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasRoles;

    public const ROLE_WEBMASTER = 'webmaster';
    public const ROLE_MERCHANT  = 'merchant';

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
