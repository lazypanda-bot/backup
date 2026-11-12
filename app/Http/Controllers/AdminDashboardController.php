<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $properties = $this->countTable('listings');
        $forSale = DB::table('listings')->where('status', 'available')->count();
        $pending = DB::table('listings')->where('status', 'reserved')->count();
        $sold = DB::table('listings')->where('status', 'sold')->count();
        $inactive = DB::table('listings')->where('approval_status', 'rejected')->count();

        $totalProperties = max($properties, 1);

        $propertyStatus = [
            [
                'label' => 'For Sale',
                'count' => $forSale,
                'percent' => round($forSale / $totalProperties * 100),
                'color' => '#10c25a',
            ],
            [
                'label' => 'Pending',
                'count' => $pending,
                'percent' => round($pending / $totalProperties * 100),
                'color' => '#f5a623',
            ],
            [
                'label' => 'Sold',
                'count' => $sold,
                'percent' => round($sold / $totalProperties * 100),
                'color' => '#3b82f6',
            ],
            [
                'label' => 'Inactive',
                'count' => $inactive,
                'percent' => round($inactive / $totalProperties * 100),
                'color' => '#9ca3af',
            ],
        ];

        $stats = [
            'properties' => $properties,
            'users' => $this->countTable('users'),
            'appointments' => $this->countTable('appointments'),
            'messages' => $this->countTable('messages'),
            'propertyStatus' => $propertyStatus,
        ];

        $activities = $this->getRecentActivities();

        return view('admin.dashboard', compact('stats', 'activities'));
    }

    protected function getRecentActivities(): array
    {
        $activities = [];

        $latestUser = DB::table('users')->orderBy('created_at', 'desc')->first();
        if ($latestUser) {
            $activities[] = [
                'icon' => '👤',
                'title' => 'New user registered',
                'description' => sprintf('%s was added as a new user.', $latestUser->name ?? 'A new user'),
                'time' => Carbon::parse($latestUser->created_at)->diffForHumans(),
            ];
        }

        $secondUser = DB::table('users')->orderBy('created_at', 'desc')->skip(1)->first();
        if ($secondUser) {
            $activities[] = [
                'icon' => '🧑‍💼',
                'title' => 'New broker added',
                'description' => sprintf('%s was added as a new broker.', $secondUser->name ?? 'A new broker'),
                'time' => Carbon::parse($secondUser->created_at)->diffForHumans(),
            ];
        }

        $latestListing = DB::table('listings')->orderBy('updated_at', 'desc')->first();
        if ($latestListing) {
            $activities[] = [
                'icon' => '🏠',
                'title' => 'Property updated',
                'description' => sprintf('%s was updated.', $latestListing->title ?? 'A property'),
                'time' => Carbon::parse($latestListing->updated_at)->diffForHumans(),
            ];
        }

        $latestAppointment = DB::table('appointments')->orderBy('created_at', 'desc')->first();
        if ($latestAppointment) {
            $eventLabel = 'New appointment request';
            $eventDescription = 'A new appointment request was submitted.';

            $appointmentListing = DB::table('listings')->where('id', $latestAppointment->listing_id ?? 0)->first();
            if ($appointmentListing) {
                $eventDescription = sprintf('Site visit requested for %s.', $appointmentListing->title);
            }

            $activities[] = [
                'icon' => '📅',
                'title' => $eventLabel,
                'description' => $eventDescription,
                'time' => Carbon::parse($latestAppointment->created_at)->diffForHumans(),
            ];
        }

        $latestStatusChange = DB::table('listings')
            ->whereIn('status', ['sold', 'reserved', 'available'])
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($latestStatusChange) {
            $activities[] = [
                'icon' => '✅',
                'title' => 'Property status changed',
                'description' => sprintf('%s status changed to %s.', $latestStatusChange->title ?? 'A property', ucfirst($latestStatusChange->status)),
                'time' => Carbon::parse($latestStatusChange->updated_at)->diffForHumans(),
            ];
        }

        usort($activities, function ($a, $b) {
            return strtotime($b['time']) <=> strtotime($a['time']);
        });

        return array_slice($activities, 0, 5);
    }

    public function appointmentEvents()
    {
        $appointments = DB::table('appointments')->orderBy('created_at', 'desc')->get();

        $events = $appointments->map(function ($appointment) {
            $start = $this->resolveDate($appointment, ['start_at', 'appointment_date', 'date', 'created_at']);
            $title = $this->resolveText($appointment, ['title', 'subject', 'description']) ?: 'Appointment #' . $appointment->id;

            return [
                'id' => $appointment->id,
                'title' => $title,
                'start' => $start,
                'allDay' => true,
            ];
        });

        return response()->json($events);
    }

    protected function resolveDate(object $appointment, array $fields): ?string
    {
        foreach ($fields as $field) {
            if (!empty($appointment->{$field})) {
                return date('Y-m-d', strtotime($appointment->{$field}));
            }
        }

        return null;
    }

    protected function resolveText(object $appointment, array $fields): ?string
    {
        foreach ($fields as $field) {
            if (!empty($appointment->{$field})) {
                return (string) $appointment->{$field};
            }
        }

        return null;
    }

    protected function countTable(string $table): ?int
    {
        try {
            return DB::table($table)->count();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
