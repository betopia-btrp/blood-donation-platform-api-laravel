<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Event;
use App\Models\EventRegistration;

class EventDiscoveryTest extends TestCase
{
    private function createEvent(string $status = 'upcoming'): Event
    {
        $org = $this->createUser('organization');
        return Event::create([
            'organization_id' => $org->organization->id,
            'title' => 'Blood Drive',
            'event_date' => now()->addDays(7),
            'location' => 'Dhaka',
            'district' => 'Dhaka',
            'division' => 'Dhaka',
            'max_capacity' => 50,
            'status' => $status,
        ]);
    }

    public function test_guest_can_see_events()
    {
        $this->createEvent();

        $response = $this->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['events', 'total']
            ]);
    }

    public function test_pending_events_not_visible_publicly()
    {
        $this->createEvent('pending');

        $response = $this->getJson('/api/events');
        $total = $response->json('data.total');

        $this->assertEquals(0, $total);
    }

    public function test_authenticated_user_sees_is_registered_field()
    {
        $this->createEvent();
        $user = $this->createUser('user');

        $response = $this->getJson('/api/events', $this->authHeader($user));
        $events = $response->json('data.events');

        if (count($events) > 0) {
            $this->assertArrayHasKey('is_registered', $events[0]);
        }
    }

    public function test_user_can_register_for_event()
    {
        $event = $this->createEvent();
        $user = $this->createUser('user');

        $response = $this->postJson(
            '/api/events/' . $event->id . '/register',
            [],
            $this->authHeader($user)
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'profile_id' => $user->profile->id,
        ]);
    }

    public function test_user_cannot_register_twice()
    {
        $event = $this->createEvent();
        $user = $this->createUser('user');

        $this->postJson('/api/events/' . $event->id . '/register', [], $this->authHeader($user));
        $response = $this->postJson('/api/events/' . $event->id . '/register', [], $this->authHeader($user));

        $response->assertStatus(400);
    }

    public function test_user_can_cancel_registration()
    {
        $event = $this->createEvent();
        $user = $this->createUser('user');

        $this->postJson('/api/events/' . $event->id . '/register', [], $this->authHeader($user));

        $response = $this->deleteJson(
            '/api/events/' . $event->id . '/register',
            [],
            $this->authHeader($user)
        );

        $response->assertStatus(200);
    }

    public function test_event_full_prevents_registration()
    {
        $org = $this->createUser('organization');
        $event = Event::create([
            'organization_id' => $org->organization->id,
            'title' => 'Full Event',
            'event_date' => now()->addDays(7),
            'location' => 'Dhaka',
            'district' => 'Dhaka',
            'division' => 'Dhaka',
            'max_capacity' => 1,
            'status' => 'upcoming',
        ]);

        $user1 = $this->createUser('user');
        $user2 = $this->createUser('user');

        $this->postJson('/api/events/' . $event->id . '/register', [], $this->authHeader($user1));
        $response = $this->postJson('/api/events/' . $event->id . '/register', [], $this->authHeader($user2));

        $response->assertStatus(400);
    }

    public function test_guest_cannot_register()
    {
        $event = $this->createEvent();
        $response = $this->postJson('/api/events/' . $event->id . '/register');

        $response->assertStatus(401);
    }
}
