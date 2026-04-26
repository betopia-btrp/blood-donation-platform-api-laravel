<?php

namespace App\Http\Controllers\API\Organization;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Payment;
use App\Models\UserProfile;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class EventController extends Controller
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

    public function index()
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $org = $user->organization;
        if (!$org) return $this->error('Organization profile not found', 404);

        $events = Event::where('organization_id', $org->id)
            ->orderBy('event_date', 'asc')
            ->paginate(20);

        return $this->success([
            'events'       => $events->items(),
            'current_page' => $events->currentPage(),
            'last_page'    => $events->lastPage(),
            'total'        => $events->total(),
        ], 'Events retrieved');
    }

    public function store(Request $request)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $org = $user->organization;
        if (!$org) return $this->error('Organization profile not found', 404);

        $validated = $request->validate([
            'title'        => 'required|string|max:150',
            'description'  => 'nullable|string',
            'event_date'   => 'required|date',
            'location'     => 'required|string|max:255',
            'district'     => 'required|string|max:100',
            'division'     => 'required|string|max:100',
            'max_capacity' => 'nullable|integer|min:1',
            'banner_image' => 'nullable|string',
        ]);

        $event = Event::create([
            'organization_id' => $org->id,
            'title'           => $validated['title'],
            'description'     => $validated['description'] ?? null,
            'event_date'      => $validated['event_date'],
            'location'        => $validated['location'],
            'district'        => $validated['district'],
            'division'        => $validated['division'],
            'max_capacity'    => $validated['max_capacity'] ?? 100,
            'banner_image'    => $validated['banner_image'] ?? null,
            'status'          => 'pending',
        ]);

        Payment::create([
            'event_id'      => $event->id,
            'payer_user_id' => $user->id,
            'amount'        => 0,
            'status'        => 'confirmed',
            'confirmed_at'  => now(),
        ]);

        return $this->success($event, 'Event created. Pending admin approval.', 201);
    }

    public function show($id)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $org = $user->organization;
        if (!$org) return $this->error('Organization profile not found', 404);

        $event = Event::where('organization_id', $org->id)->find($id);
        if (!$event) return $this->error('Event not found', 404);

        return $this->success($event, 'Event details retrieved');
    }

    public function update(Request $request, $id)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $org = $user->organization;
        if (!$org) return $this->error('Organization profile not found', 404);

        $event = Event::where('organization_id', $org->id)->find($id);
        if (!$event) return $this->error('Event not found', 404);

        $validated = $request->validate([
            'title'        => 'sometimes|required|string|max:150',
            'description'  => 'nullable|string',
            'event_date'   => 'sometimes|required|date',
            'location'     => 'sometimes|required|string|max:255',
            'district'     => 'sometimes|required|string|max:100',
            'division'     => 'sometimes|required|string|max:100',
            'max_capacity' => 'nullable|integer|min:1',
            'banner_image' => 'nullable|string',
        ]);

        $event->update($validated);

        return $this->success($event, 'Event updated');
    }

    public function destroy($id)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $org = $user->organization;
        if (!$org) return $this->error('Organization profile not found', 404);

        $event = Event::where('organization_id', $org->id)->find($id);
        if (!$event) return $this->error('Event not found', 404);

        $event->update(['status' => 'cancelled']);

        return $this->success(null, 'Event cancelled');
    }

    public function registrations($id)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $org = $user->organization;
        if (!$org) return $this->error('Organization profile not found', 404);

        $event = Event::where('organization_id', $org->id)->find($id);
        if (!$event) return $this->error('Event not found', 404);

        $registrations = EventRegistration::with([
            'profile:id,user_id,blood_group,district,trust_score',
            'profile.user:id,name,email',
        ])
            ->where('event_id', $id)
            ->paginate(20);

        $data = collect($registrations->items())->map(fn($item) => [
            'registration_id'   => $item->id,
            'name'              => $item->profile->user->name,
            'email'             => $item->profile->user->email,
            'blood_group'       => $item->profile->blood_group,
            'district'          => $item->profile->district,
            'trust_score'       => $item->profile->trust_score,
            'attendance_status' => $item->attendance_status,
            'registration_date' => $item->registration_date,
        ]);

        return $this->success([
            'registrations' => $data,
            'current_page'  => $registrations->currentPage(),
            'last_page'     => $registrations->lastPage(),
            'total'         => $registrations->total(),
        ], 'Registrations retrieved');
    }

    public function updateAttendance(Request $request, $id)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $org = $user->organization;
        if (!$org) return $this->error('Organization profile not found', 404);

        $request->validate([
            'profile_id'        => 'required|exists:user_profiles,id',
            'attendance_status' => 'required|in:attended,absent',
        ]);

        $event = Event::where('organization_id', $org->id)->find($id);
        if (!$event) return $this->error('Event not found', 404);

        $registration = EventRegistration::where('event_id', $event->id)
            ->where('profile_id', $request->profile_id)
            ->first();

        if (!$registration) return $this->error('Registration not found', 404);

        $isBecomingAbsent = ($request->attendance_status === 'absent'
            && $registration->attendance_status !== 'absent');

        $registration->update(['attendance_status' => $request->attendance_status]);

        if ($isBecomingAbsent) {
            $profile = UserProfile::find($request->profile_id);
            if ($profile) {
                $profile->trust_score = max(0.00, $profile->trust_score - 0.10);
                $profile->save();
            }
        }

        return $this->success(null, 'Attendance updated');
    }
}
