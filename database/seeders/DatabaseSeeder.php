<?php

namespace Database\Seeders;

use Domain\Shared\Models\User;
use Domain\Surveys\Permissions\SurveyPermission;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // TODO: Refactor this

        $admin = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Artisan::call('permission:sync --path=Domain');

        $role = Role::create(['name' => 'admin', 'guard_name' => 'api']);

        $role->syncPermissions([
            SurveyPermission::VIEW_ANY->value,
            SurveyPermission::VIEW->value,
            SurveyPermission::CREATE->value,
            SurveyPermission::UPDATE->value,
            SurveyPermission::DELETE->value,
        ]);

        $admin->assignRole($role);

        // Seed companies and surveys
        $this->call([
            SurveySeeder::class,
        ]);

        // extra company
        $company = \Domain\Companies\Models\Company::factory()->create([
            'name' => 'Company 2',
        ]);
    }
}
