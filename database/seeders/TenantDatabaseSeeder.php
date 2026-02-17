<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('tenant_admin', 'web');
        Role::findOrCreate('student', 'web');

        $currentTenant = tenant();
        $ownerEmail = $currentTenant?->getAttribute('owner_email');

        if (! $ownerEmail) {
            return;
        }

        $owner = User::query()->firstOrCreate(
            ['email' => $ownerEmail],
            [
                'name' => $currentTenant->getAttribute('owner_name') ?? 'Tenant Admin',
                'password' => $currentTenant->getAttribute('owner_password'),
            ]
        );

        if (! $owner->hasRole('tenant_admin')) {
            $owner->assignRole('tenant_admin');
        }
    }
}
