<?php

namespace App\Services;

use App\Mail\ActivityMail;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AccountStatusService
{
    public function deactivateForInactivity(User $user): void
    {
        if ($user->account_status !== 'Active') {
            return;
        }

        $user->update(['account_status' => 'Inactive']);

        AppNotification::create([
            'user_id' => $user->user_id,
            'type' => 'team',
            'title' => 'Account inactive',
            'body' => 'Your account was automatically marked inactive after 7 days without activity. Contact an Admin to reactivate it.',
            'url' => route('home'),
        ]);
        NotificationService::oversight([
            'type' => 'team',
            'title' => 'Account deactivated after inactivity',
            'body' => $user->name . ' has been inactive for 7 days. Contact them or reactivate the account from Users.',
            'url' => route('admin.users.index'),
        ], null, [$user]);

        Mail::to($user->email, $user->name)->queue(new ActivityMail(
            'Your LJS Realty account is inactive',
            "Your account was automatically marked inactive because there has been no activity for 7 days.\n\nContact an Admin to reactivate your account."
        ));
    }

    public function notifyStatusChange(User $user, string $previousStatus): void
    {
        if ($previousStatus === $user->account_status) {
            return;
        }

        $reactivated = $user->account_status === 'Active' && $previousStatus !== 'Active';
        $title = $reactivated ? 'Account reactivated' : 'Account inactive';
        $body = $reactivated
            ? 'Your LJS Realty account has been reactivated by an Admin. You can sign in again.'
            : 'Your LJS Realty account has been marked inactive by an Admin. Contact an Admin if you need it reactivated.';

        AppNotification::create([
            'user_id' => $user->user_id,
            'type' => 'team',
            'title' => $title,
            'body' => $body,
            'url' => route('home'),
        ]);
        Mail::to($user->email, $user->name)->queue(new ActivityMail($title, $body));
    }
}
