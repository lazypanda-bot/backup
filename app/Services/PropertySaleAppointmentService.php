<?php

namespace App\Services;

use App\Mail\ActivityMail;
use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\Property;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PropertySaleAppointmentService
{
    private const CANCELLATION_REASON = 'The property has been sold, so this appointment is no longer available.';

    public function cancelUpcomingAppointments(Property $property): int
    {
        $now = CarbonImmutable::now('UTC');
        $cancelledAppointments = DB::transaction(function () use ($property, $now): Collection {
            $appointments = Appointment::query()
                ->with(['client', 'assignment.user'])
                ->whereIn('appointment_status', ['Pending', 'Confirmed'])
                ->whereHas('assignment', fn ($query) => $query->where('property_id', $property->property_id))
                ->where(fn ($query) => $query
                    ->where('starts_at_utc', '>', $now->format('Y-m-d H:i:s'))
                    ->orWhereNull('starts_at_utc'))
                ->orderBy('appointment_id')
                ->lockForUpdate()
                ->get()
                ->filter(fn (Appointment $appointment) => $appointment->startInTimezone('UTC')->greaterThan($now))
                ->values();

            foreach ($appointments as $appointment) {
                $appointment->update([
                    'appointment_status' => 'Cancelled',
                    'cancellation_reason' => self::CANCELLATION_REASON,
                ]);
                $appointment->proposals()
                    ->where('status', 'Pending')
                    ->update(['status' => 'Rejected', 'responded_at' => now()]);

                $recipients = collect([$appointment->client, $appointment->assignment?->user])
                    ->filter(fn ($recipient) => $recipient instanceof User)
                    ->unique('user_id');

                foreach ($recipients as $recipient) {
                    AppNotification::create([
                        'user_id' => $recipient->user_id,
                        'type' => 'listing',
                        'title' => 'Appointment cancelled',
                        'body' => $property->property_name . ' has been sold. This upcoming appointment was automatically cancelled.',
                        'url' => route('notifications.index'),
                        'appointment_id' => $appointment->appointment_id,
                    ]);
                }
            }

            return $appointments;
        });

        foreach ($cancelledAppointments as $appointment) {
            foreach ($this->emailRecipients($appointment) as $recipient) {
                $timezone = $recipient['timezone'] ?: ($appointment->client_timezone ?: config('appointments.default_timezone'));
                $when = $appointment->startInTimezone($timezone)->format('M j, Y') . ' at '
                    . $appointment->startInTimezone($timezone)->format('g:i A') . " ({$timezone})";
                $subject = 'Appointment cancelled: ' . $property->property_name;
                $body = $recipient['type'] === 'client'
                    ? "We are sorry to let you know that {$property->property_name} has been sold. Your appointment scheduled for {$when} has been cancelled. We apologize for the inconvenience and appreciate your understanding."
                    : "Please be advised that {$property->property_name} has been sold. The upcoming appointment with {$appointment->contact_name}, scheduled for {$when}, has been automatically cancelled. Please contact the client if follow-up is needed.";

                try {
                    Mail::to($recipient['email'], $recipient['name'])->queue(new ActivityMail($subject, $body));
                } catch (\Throwable $exception) {
                    Log::warning('Sold-property appointment email could not be queued.', [
                        'property_id' => $property->property_id,
                        'appointment_id' => $appointment->appointment_id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return $cancelledAppointments->count();
    }

    /**
     * @return Collection<int, array{email: string, name: string, type: string, timezone: ?string}>
     */
    private function emailRecipients(Appointment $appointment): Collection
    {
        $recipients = collect();
        $client = $appointment->client;
        if ($client instanceof User) {
            $recipients->push([
                'email' => $client->email,
                'name' => $client->name,
                'type' => 'client',
                'timezone' => $client->timezone,
            ]);
        } elseif (filled($appointment->contact_email)) {
            $recipients->push([
                'email' => $appointment->contact_email,
                'name' => $appointment->contact_name,
                'type' => 'client',
                'timezone' => $appointment->client_timezone,
            ]);
        }

        $professional = $appointment->assignment?->user;
        if ($professional instanceof User) {
            $recipients->push([
                'email' => $professional->email,
                'name' => $professional->name,
                'type' => 'professional',
                'timezone' => $professional->timezone,
            ]);
        }

        return $recipients
            ->filter(fn (array $recipient) => filled($recipient['email']))
            ->unique(fn (array $recipient) => strtolower($recipient['email']))
            ->values();
    }
}
