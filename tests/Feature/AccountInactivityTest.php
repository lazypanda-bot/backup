<?php

namespace Tests\Feature;

use App\Mail\ActivityMail;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountInactivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_non_admin_account_is_deactivated_and_notified_after_seven_days(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $agent = User::factory()->create([
            'role_name' => 'Agent',
            'account_status' => 'Active',
            'last_active_at' => now()->subDays(8),
        ]);

        Artisan::call('accounts:deactivate-inactive');

        $this->assertSame('Inactive', $agent->fresh()->account_status);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $agent->user_id,
            'title' => 'Account inactive',
        ]);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $admin->user_id,
            'title' => 'Account deactivated after inactivity',
        ]);
        Mail::assertQueued(ActivityMail::class, fn (ActivityMail $mail) => $mail->hasTo($agent->email)
            && str_contains($mail->messageBody, 'Contact an Admin'));
    }

    public function test_admin_reactivation_notifies_the_user(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $client = User::factory()->create(['role_name' => 'Client', 'account_status' => 'Inactive']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $client), ['account_status' => 'Active'])
            ->assertRedirect();

        $this->assertSame('Active', $client->fresh()->account_status);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $client->user_id,
            'title' => 'Account reactivated',
        ]);
        Mail::assertQueued(ActivityMail::class, fn (ActivityMail $mail) => $mail->hasTo($client->email)
            && $mail->subjectLine === 'Account reactivated');
    }

    public function test_blocking_a_date_stays_on_the_dashboard_month(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $date = now()->addDays(5)->toDateString();

        $this->actingAs($admin)
            ->post(route('appointments.block-date'), [
                'date' => $date,
                'time_slot' => 'whole_day',
                '_return_to' => route('appointments.index', absolute: false),
            ])
            ->assertRedirect(route('app.page', [
                'page' => 'dashboard',
                'month' => now()->parse($date)->month,
                'year' => now()->parse($date)->year,
            ]));
    }
}
