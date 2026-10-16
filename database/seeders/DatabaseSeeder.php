<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    // Creates the two roles and the first webmaster. Set WEBMASTER_USERNAME /
    // WEBMASTER_PASSWORD in .env (the webmaster then creates the merchant
    // login from the Merchant Account page). Idempotent.
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([User::ROLE_WEBMASTER, User::ROLE_MERCHANT] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $username = env('WEBMASTER_USERNAME', 'webmaster');
        $password = env('WEBMASTER_PASSWORD');

        if (! $password) {
            $this->command?->warn('WEBMASTER_PASSWORD is not set — skipping webmaster creation.');

            return;
        }

        $webmaster = User::firstOrNew(['username' => $username]);
        $webmaster->name ??= 'Webmaster';
        $webmaster->password = $password;
        $webmaster->save();
        $webmaster->syncRoles([User::ROLE_WEBMASTER]);

        $this->command?->info("Webmaster '{$username}' is ready.");
    }
}
