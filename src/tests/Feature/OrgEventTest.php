<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Event;
use App\Models\EventRegistration;

class OrgEventTest extends TestCase
{
    private function createEventPayload(): array
    {
        return [
            'title' => 'Blood Donation Drive',
            'description' => 'Community blood drive',
            'event_date' => now()->addDays(10)->toDateTimeString(),
            'location' => 'Dhaka Medical College',
            'district' => 'Dhaka',
            'division' => 'Dhaka',
            'max_capacity' => 50,
        ];
    }

    public function test_org_can_create_event()
    {
        $org = $this->createUser('organization');
        $response = $this->postJson(
            '/api/dashboard/org/events',
            $this->createEventPayload(),
            $this->authHeader($org)
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('events', [
            'organization_id' => $org->organization->id,
            'status' => 'pending',
        ]);
    }

    public function test_event_auto_creates_payment_record()
    {
        $org = $this->createUser('organization');
        $this->postJson(
            '/api/dashboard/org/events',
            $this->createEventPayload(),
            $this->authHeader($org)
        );

        $event = Event::where('organization_id', $org->organization->id)->first();

        $this->assertDatabaseHas('payments', [
            'event_id' => $event->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_org_can_update_own_event()
    {
        $org = $this->createUser('organization');
        $event = Event::create([
            'organization_id' => $org->organization->id,
            'title' => 'Old Title',
            'event_date' => now()->addDays(7),
            'location' => 'Dhaka',
            'district' => 'Dhaka',
            'division' => 'Dhaka',
            'max_capacity' => 50,
            'status' => 'pending',
        ]);

        $response = $this->putJson(
            '/api/dashboard/org/events/' . $event->id,
            ['title' => 'New Title'],
            $this->authHeader($org)
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'New Title',
        ]);
    }

    public function test_org_cannot_update_other_org_event()
    {
        $org1 = $this->createUser('organization');
        $org2 = $this->createUser('organization');
        $event = Event::create([
            'organization_id' => $org1->organization->id,
            'title' => 'Org1 Event',
            'event_date' => now()->addDays(7),
            'location' => 'Dhaka',
            'district' => 'Dhaka',
            'division' => 'Dhaka',
            'max_capacity' => 50,
            'status' => 'pending',
        ]);

        $response = $this->putJson(
            '/api/dashboard/org/events/' . $event->id,
            ['title' => 'Hacked Title'],
            $this->authHeader($org2)
        );

        $response->assertStatus(404);
    }

    public function test_org_can_cancel_event()
    {
        $org = $this->createUser('organization');
        $event = Event::create([
            'organization_id' => $org->organization->id,
            'title' => 'Test Event',
            'event_date' => now()->addDays(7),
            'location' => 'Dhaka',
            'district' => 'Dhaka',
            'division' => 'Dhaka',
            'max_capacity' => 50,
            'status' => 'upcoming',
        ]);

        $response = $this->deleteJson(
            '/api/dashboard/org/events/' . $event->id,
            [],
            $this->authHeader($org)
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_org_can_see_registrations()
    {
        $org = $this->createUser('organization');
        $user = $this->createUser('user');
        $event = Event::create([
            'organization_id' => $org->organization->id,
            'title' => 'Test Event',
            'event_date' => now()->addDays(7),
            'location' => 'Dhaka',
            'district' => 'Dhaka',
            'division' => 'Dhaka',
            'max_capacity' => 50,
            'status' => 'upcoming',
        ]);

        EventRegistration::create([
            'event_id' => $event->id,
            'profile_id' => $user->profile->id,
            'registration_date' => now(),
            'attendance_status' => 'registered',
        ]);

        $response = $this->getJson(
            '/api/dashboard/org/events/' . $event->id . '/registrations',
            $this->authHeader($org)
        );

        $response->assertStatus(200);
    }

    public function test_org_can_mark_attendance()
    {
        $org = $this->createUser('organization');
        $user = $this->createUser('user');
        $event = Event::create([
            'organization_id' => $org->organization->id,
            'title' => 'Test Event',
            'event_date' => now()->addDays(7),
            'location' => 'Dhaka',
            'district' => 'Dhaka',
            'division' => 'Dhaka',
            'max_capacity' => 50,
            'status' => 'upcoming',
        ]);

        EventRegistration::create([
            'event_id' => $event->id,
            'profile_id' => $user->profile->id,
            'registration_date' => now(),
            'attendance_status' => 'registered',
        ]);

        $response = $this->putJson(
            '/api/dashboard/org/events/' . $event->id . '/attendance',
            [
                'profile_id' => $user->profile->id,
                'attendance_status' => 'attended',
            ],
            $this->authHeader($org)
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'profile_id' => $user->profile->id,
            'attendance_status' => 'attended',
        ]);
    }

    public function test_absent_donor_loses_trust_score()
    {
        $org = $this->createUser('organization');
        $user = $this->createUser('user');
        $event = Event::create([
            'organization_id' => $org->organization->id,
            'title' => 'Test Event',
            'event_date' => now()->addDays(7),
            'location' => 'Dhaka',
            'district' => 'Dhaka',
            'division' => 'Dhaka',
            'max_capacity' => 50,
            'status' => 'upcoming',
        ]);

        EventRegistration::create([
            'event_id' => $event->id,
            'profile_id' => $user->profile->id,
            'registration_date' => now(),
            'attendance_status' => 'registered',
        ]);

        $before = $user->profile->trust_score;

        $this->putJson(
            '/api/dashboard/org/events/' . $event->id . '/attendance',
            [
                'profile_id' => $user->profile->id,
                'attendance_status' => 'absent',
            ],
            $this->authHeader($org)
        );

        $user->profile->refresh();
        $this->assertLessThan($before, $user->profile->trust_score);
    }

    public function test_user_cannot_create_event()
    {
        $user = $this->createUser('user');
        $response = $this->postJson(
            '/api/dashboard/org/events',
            $this->createEventPayload(),
            $this->authHeader($user)
        );

        $response->assertStatus(403);
    }
}
