<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\GitlabService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        $seedPassword = (string) env('SEED_PASSWORD', 'school21');

        $adminUser = User::firstOrNew(['email' => 'admin@gmail.com']);
        $adminUser->forceFill([
            'name' => 'Admin User',
            'username' => 'lms-admin',
            'email_verified_at' => now(),
            'password' => Hash::make($seedPassword),
        ])->save();
        $adminUser->syncRoles([$adminRole]);

        $testUser = User::firstOrNew(['email' => 'test@gmail.com']);
        $testUser->forceFill([
            'name' => 'Test User',
            'username' => 'test',
            'email_verified_at' => now(),
            'password' => Hash::make($seedPassword),
        ])->save();
        $testUser->syncRoles([$userRole]);

        $test1User = User::firstOrNew(['email' => 'test1@gmail.com']);
        $test1User->forceFill([
            'name' => 'Test Reviewer One',
            'username' => 'test1',
            'email_verified_at' => now(),
            'password' => Hash::make($seedPassword),
        ])->save();
        $test1User->syncRoles([$userRole]);

        $test2User = User::firstOrNew(['email' => 'test2@gmail.com']);
        $test2User->forceFill([
            'name' => 'Test Reviewer',
            'username' => 'test2',
            'email_verified_at' => now(),
            'password' => Hash::make($seedPassword),
        ])->save();
        $test2User->syncRoles([$userRole]);

        collect([$adminUser, $testUser, $test1User, $test2User])->each(function (User $user) use ($seedPassword) {
            try {
                app(GitlabService::class)->ensureUserAccount($user, $seedPassword);
                $this->command?->info("GitLab account ready: {$user->username} <{$user->email}>");
            } catch (\Throwable $e) {
                // GitLab is an external dependency: a fresh install without
                // GITLAB_TOKEN must still be able to seed and log in.
                Log::warning('Seed GitLab account provisioning skipped', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'username' => $user->username,
                    'error' => $e->getMessage(),
                ]);

                $this->command?->warn("GitLab account skipped for {$user->username} <{$user->email}>: {$e->getMessage()}");
            }
        });

        User::whereDoesntHave('roles')
            ->get()
            ->each(fn (User $user) => $user->assignRole($userRole));

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
