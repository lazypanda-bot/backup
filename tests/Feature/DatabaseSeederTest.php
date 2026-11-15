<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_admin_seeding_requires_explicit_configuration(): void
    {
        config()->set('initial-admin', [
            'name' => null,
            'email' => null,
            'password' => null,
        ]);

        $this->expectException(\RuntimeException::class);

        $this->seed(DatabaseSeeder::class);
    }

    public function test_initial_admin_is_created_from_config_and_rerunning_does_not_reset_password(): void
    {
        config()->set('initial-admin', [
            'name' => 'Demo Lead Broker',
            'email' => 'demo-lead-broker@example.test',
            'password' => 'first-temporary-password',
        ]);

        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'demo-lead-broker@example.test')->firstOrFail();

        $this->assertSame('Demo Lead Broker', $admin->name);
        $this->assertSame('Lead Broker', $admin->role_name);
        $this->assertTrue(Hash::check('first-temporary-password', $admin->password));

        config()->set('initial-admin.password', 'second-temporary-password');
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Hash::check('first-temporary-password', $admin->fresh()->password));
    }
}
