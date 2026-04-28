<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\RoleScope;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => RoleName::SUPER_ADMIN->value, 'scope' => RoleScope::GLOBAL->value],
            ['name' => RoleName::COMPANY_ADMIN->value, 'scope' => RoleScope::COMPANY->value],
            ['name' => RoleName::MANAGER->value, 'scope' => RoleScope::PROJECT->value],
            ['name' => RoleName::TECHNICAL_MANAGER->value, 'scope' => RoleScope::PROJECT->value],
            ['name' => RoleName::TEAM_LEAD->value, 'scope' => RoleScope::PROJECT->value],
            ['name' => RoleName::TEAM_MEMBER->value, 'scope' => RoleScope::PROJECT->value],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['name' => $role['name']],
                ['scope' => $role['scope']]
            );
        }
    }
}
