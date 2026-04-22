<?php

namespace App\Http\Controllers\API\Organization;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    // Ensure we get the organization ID for the logged-in user
    private function getOrganizationId(Request $request)
    {
        // Assuming your User model has an organization() relationship
        return $request->user()->organization->id; 
    }

    // 1. GET /api/organization/events -> own events list
    public function index(Request $request)
    {
        $events = Event::where('organization_id', $this->getOrganizationId($request))
            ->orderBy('event_date', 'asc')
            ->get();

        return response()->json(['data' => $events], 200);
    }

    // 2. POST /api/organization/events -> create event
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'location' => 'required|string|max:255',
            'district' => 'required|string|max:100',
            'division' => 'required|string|max:100',
            'max_capacity' => 'nullable|integer|min:1',
            'banner_image' => 'nullable|string',
        ]);

        $event = Event::create([
            'organization_id' => $this->getOrganizationId($request),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'event_date' => $validated['event_date'],
            'location' => $validated['location'],
            'district' => $validated['district'],
            'division' => $validated['division'],
            'max_capacity' => $validated['max_capacity'] ?? 100,
            'banner_image' => $validated['banner_image'] ?? null,
            'status' => 'upcoming',
        ]);

        return response()->json(['message' => 'Event created successfully', 'data' => $event], 201);
    }

    // 3. GET /api/organization/events/{id} -> event details
    public function show($id, Request $request)
    {
        $event = Event::where('organization_id', $this->getOrganizationId($request))->findOrFail($id);
        return response()->json(['data' => $event], 200);
    }

    // 4. PUT /api/organization/events/{id} -> update event
    public function update($id, Request $request)
    {
        $event = Event::where('organization_id', $this->getOrganizationId($request))->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:150',
            'description' => 'nullable|string',
            'event_date' => 'sometimes|required|date',
            'location' => 'sometimes|required|string|max:255',
            'district' => 'sometimes|required|string|max:100',
            'division' => 'sometimes|required|string|max:100',
            'max_capacity' => 'nullable|integer|min:1',
            'status' => 'sometimes|required|in:upcoming,completed,cancelled'
        ]);

        $event->update($validated);

        return response()->json(['message' => 'Event updated successfully', 'data' => $event], 200);
    }

    // 5. DELETE /api/organization/events/{id} -> cancel event
    public function destroy($id, Request $request)
    {
        $event = Event::where('organization_id', $this->getOrganizationId($request))->findOrFail($id);
        
        // Instead of hard deleting, we mark it as cancelled per the migration enum
        $event->update(['status' => 'cancelled']); 

        return response()->json(['message' => 'Event cancelled successfully'], 200);
    }

    // 6. GET /api/organization/events/{id}/registrations -> registered donors list
    public function registrations($id, Request $request)
    {
        $event = Event::where('organization_id', $this->getOrganizationId($request))->findOrFail($id);
        
        $registrations = $event->registrations()->with('profile.user')->get();
        
        return response()->json(['data' => $registrations], 200);
    }

    // 7. PUT /api/organization/events/{id}/attendance -> mark attended/absent
    public function updateAttendance($id, Request $request)
    {
        $request->validate([
            'profile_id' => 'required|exists:user_profiles,id',
            'attendance_status' => 'required|in:attended,absent'
        ]);

        $event = Event::where('organization_id', $this->getOrganizationId($request))->findOrFail($id);
        
        $registration = EventRegistration::where('event_id', $event->id)
            ->where('profile_id', $request->profile_id)
            ->firstOrFail();

        DB::transaction(function () use ($registration, $request) {
            // Check if status is actually changing to absent to prevent double penalties
            $isBecomingAbsent = ($request->attendance_status === 'absent' && $registration->attendance_status !== 'absent');
            
            $registration->update(['attendance_status' => $request->attendance_status]);

            if ($isBecomingAbsent) {
                $profile = UserProfile::find($request->profile_id);
                if ($profile) {
                    $currentScore = $profile->trust_score ?? 1.00;
                    $profile->update([
                        'trust_score' => max(0.00, $currentScore - 0.10)
                    ]);
                }
            }
        });

        return response()->json(['message' => 'Attendance updated successfully'], 200);
    }
}