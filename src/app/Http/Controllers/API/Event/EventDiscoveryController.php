<?php

namespace App\Http\Controllers\API\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Report;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class EventDiscoveryController extends Controller
{
    use ApiResponse;

    private function getOptionalUser()
    {
        try {
            $token = JWTAuth::getToken();
            if (!$token) return null;
            return JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return null;
        }
    }

    // For Guest or Authenticate User
    private function injectEventContext(object $event, $user): array
    {
        $data = $event->toArray();

        if ($user && $user->profile) {
            $registration = EventRegistration::where('event_id', $event->id)
                ->where('profile_id', $user->profile->id)
                ->first();

            $data['is_registered']     = (bool) $registration;
            $data['registration_id']   = $registration?->id;
            $data['attendance_status'] = $registration?->attendance_status;
        } else {
            $data['is_registered']     = null;
            $data['registration_id']   = null;
            $data['attendance_status'] = null;
        }

        return $data;
    }

    public function index(Request $request)
    {
        $user  = $this->getOptionalUser();
        $query = Event::with(['organization:id,org_name'])
            ->withCount('registrations')
            ->where('status', 'upcoming');

        if ($request->filled('district')) {
            $query->where('district', 'ilike', '%' . $request->district . '%');
        }
        if ($request->filled('division')) {
            $query->where('division', 'ilike', '%' . $request->division . '%');
        }

        $events = $query->orderBy('event_date', 'asc')->paginate(20);

        $registeredEventIds = [];
        $registrationData   = [];

        if ($user && $user->profile) {
            $registrations = EventRegistration::whereIn('event_id', collect($events->items())->pluck('id'))
                ->where('profile_id', $user->profile->id)
                ->get(['event_id', 'id', 'attendance_status']);

            foreach ($registrations as $r) {
                $registeredEventIds[]        = $r->event_id;
                $registrationData[$r->event_id] = [
                    'registration_id'   => $r->id,
                    'attendance_status' => $r->attendance_status,
                ];
            }
        }

        $data = collect($events->items())->map(function ($event) use ($user, $registeredEventIds, $registrationData) {
            $arr = $event->toArray();

            if ($user && $user->profile) {
                $isRegistered              = in_array($event->id, $registeredEventIds);
                $arr['is_registered']      = $isRegistered;
                $arr['registration_id']    = $isRegistered ? $registrationData[$event->id]['registration_id'] : null;
                $arr['attendance_status']  = $isRegistered ? $registrationData[$event->id]['attendance_status'] : null;
            } else {
                $arr['is_registered']      = null;
                $arr['registration_id']    = null;
                $arr['attendance_status']  = null;
            }

            return $arr;
        });

        return $this->success([
            'events'       => $data,
            'current_page' => $events->currentPage(),
            'last_page'    => $events->lastPage(),
            'total'        => $events->total(),
        ], 'Events retrieved');
    }

    public function show($id)
    {
        $user  = $this->getOptionalUser();
        $event = Event::with(['organization:id,org_name'])
            ->withCount('registrations')
            ->where('status', 'upcoming')
            ->find($id);

        if (!$event) return $this->error('Event not found', 404);

        return $this->success(
            $this->injectEventContext($event, $user),
            'Event details retrieved'
        );
    }

    public function register($id)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $profile = $user->profile;
        if (!$profile) return $this->error('Donor profile not found', 404);

        $event = Event::where('status', 'upcoming')->find($id);
        if (!$event) return $this->error('Event not found', 404);

        $registered = EventRegistration::where('event_id', $id)->count();
        if ($registered >= $event->max_capacity) {
            return $this->error('Event is full', 400);
        }

        $existing = EventRegistration::where('event_id', $id)
            ->where('profile_id', $profile->id)
            ->first();

        if ($existing) return $this->error('Already registered for this event', 400);

        EventRegistration::create([
            'event_id'          => $id,
            'profile_id'        => $profile->id,
            'registration_date' => now(),
            'attendance_status' => 'registered',
        ]);

        return $this->success(null, 'Successfully registered for event');
    }

    public function cancelRegistration($id)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $profile = $user->profile;
        if (!$profile) return $this->error('Donor profile not found', 404);

        $registration = EventRegistration::where('event_id', $id)
            ->where('profile_id', $profile->id)
            ->first();

        if (!$registration) return $this->error('Registration not found', 404);

        if ($registration->attendance_status !== 'registered') {
            return $this->error('Cannot cancel after attendance is marked', 400);
        }

        $registration->delete();

        return $this->success(null, 'Registration cancelled');
    }

    public function report(Request $request, $id)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $event = Event::find($id);
        if (!$event) return $this->error('Event not found', 404);

        $request->validate([
            'report_type' => 'nullable|in:spam,fake,abusive,other',
            'reason'      => 'nullable|string',
        ]);

        Report::create([
            'reporter_user_id' => $user->id,
            'target_id'        => $id,
            'target_type'      => 'event',
            'report_type'      => $request->input('report_type', 'other'),
            'reason'           => $request->input('reason'),
            'status'           => 'pending',
        ]);

        return $this->success(null, 'Report submitted');
    }
}
