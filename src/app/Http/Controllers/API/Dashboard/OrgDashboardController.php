<?php

namespace App\Http\Controllers\API\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Traits\ApiResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class OrgDashboardController extends Controller
{
    use ApiResponse;

    private function getUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return null;
        }
    }

    // My Events List
    public function myEvents()
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $org = $user->organization;
        if (!$org) return $this->error('Organization profile not found', 404);

        $events = Event::where('organization_id', $org->id)
            ->withCount('registrations')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->success([
            'events'       => $events->items(),
            'current_page' => $events->currentPage(),
            'last_page'    => $events->lastPage(),
            'total'        => $events->total(),
        ], 'Organization events retrieved');
    }

    // Event Details with Registrations
    public function myEventShow($id)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $org = $user->organization;
        if (!$org) return $this->error('Organization profile not found', 404);

        $event = Event::where('id', $id)
            ->where('organization_id', $org->id)
            ->withCount([
                'registrations',
                'registrations as attended_count' => fn($q) => $q->where('attendance_status', 'attended'),
                'registrations as absent_count'   => fn($q) => $q->where('attendance_status', 'absent'),
            ])
            ->first();

        if (!$event) return $this->error('Event not found', 404);

        $registrations = EventRegistration::with([
            'profile:id,user_id,blood_group,district,trust_score',
            'profile.user:id,name,email',
        ])
            ->where('event_id', $id)
            ->get()
            ->map(fn($item) => [
                'registration_id'   => $item->id,
                'name'              => $item->profile->user->name,
                'profile_id'        => $item->profile->id,
                'email'             => $item->profile->user->email,
                'blood_group'       => $item->profile->blood_group,
                'district'          => $item->profile->district,
                'trust_score'       => $item->profile->trust_score,
                'attendance_status' => $item->attendance_status,
                'registration_date' => $item->registration_date,
            ]);

        return $this->success([
            'event'         => $event,
            'registrations' => $registrations,
        ], 'Event details retrieved');
    }

    // Organization Stats
    public function stats()
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $org = $user->organization;
        if (!$org) return $this->error('Organization profile not found', 404);

        $totalEvents = Event::where('organization_id', $org->id)->count();

        $eventsByStatus = Event::where('organization_id', $org->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalDonors = EventRegistration::whereHas('event', function ($q) use ($org) {
            $q->where('organization_id', $org->id);
        })->count();

        $totalAttended = EventRegistration::whereHas('event', function ($q) use ($org) {
            $q->where('organization_id', $org->id);
        })->where('attendance_status', 'attended')->count();

        $attendanceRate = $totalDonors > 0
            ? round($totalAttended / $totalDonors, 2)
            : 0;

        return $this->success([
            'total_events'    => $totalEvents,
            'events_by_status' => $eventsByStatus,
            'total_donors'    => $totalDonors,
            'total_attended'  => $totalAttended,
            'attendance_rate' => $attendanceRate,
        ], 'Organization stats retrieved');
    }
}
