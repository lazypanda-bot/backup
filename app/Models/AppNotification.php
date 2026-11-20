<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'app_notifications';
    protected $primaryKey = 'notification_id';

    protected $fillable = ['user_id', 'type', 'audience', 'title', 'body', 'url', 'appointment_proposal_id', 'appointment_id', 'read_at'];

    // ONLY THE NOTIFICATIONS VISIBLE TO GIVEN USER. LEAD BROKER'S VIEW DEPENDS ON ACTING ROLE.
    public function scopeVisibleTo($query, User $user)
    {
        $role = $user->normalizedRole();

        if ($role === 'broker') {
            return $query
                ->where('app_notifications.user_id', $user->user_id)
                ->where('audience', '!=', 'system');
        }

        if ($role === 'lead_broker') {
            if ($user->actingRole() === 'admin') {
                return $query
                    ->where('app_notifications.user_id', $user->user_id)
                    ->where(function ($q) {
                        $q->where('audience', 'system')
                            ->orWhere('type', 'message');
                    });
            }

            return $query
                ->where('app_notifications.user_id', $user->user_id)
                ->where('audience', '!=', 'system');
        }

        return $query->where('app_notifications.user_id', $user->user_id);
    }

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function appointmentProposal()
    {
        return $this->belongsTo(AppointmentTimeProposal::class, 'appointment_proposal_id', 'proposal_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'appointment_id');
    }
}