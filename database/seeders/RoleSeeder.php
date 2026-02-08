<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        $roles = [
            'super_admin',
            'admin',
            'user',
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role, $guard);
        }

        $user = User::where('email', 'admin@telkomsel.co.id')->first();
        if ($user && ! $user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
        }
    }
}
