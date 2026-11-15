<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminName = config('initial-admin.name');
        $adminEmail = config('initial-admin.email');
        $adminPassword = config('initial-admin.password');

        if (! is_string($adminName) || trim($adminName) === ''
            || ! is_string($adminEmail) || filter_var($adminEmail, FILTER_VALIDATE_EMAIL) === false
            || ! is_string($adminPassword) || trim($adminPassword) === '') {
            throw new \RuntimeException(
                'ADMIN_NAME, a valid ADMIN_EMAIL, and ADMIN_PASSWORD must be set before seeding the initial admin.'
            );
        }

        $admin = User::withTrashed()->firstOrNew(['email' => $adminEmail]);

        if (! $admin->exists) {
            $admin->password = Hash::make($adminPassword);
        } elseif ($admin->trashed()) {
            $admin->restore();
        }

        $admin->fill([
            'name' => $adminName,
            'account_status' => 'Active',
            'role_name' => 'Lead Broker',
        ]);
        $admin->save();
    }
}
