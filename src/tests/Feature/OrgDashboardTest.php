<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Event;
use App\Models\EventRegistration;

class OrgDashboardTest extends TestCase
{
    private function createOrgEvent($org, string $status = 'upcoming'): Event
    {
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

    public function test_org_can_see_own_events()
    {
        $org = $this->createUser('organization');
        $this->createOrgEvent($org);

        $response = $this->getJson('/api/dashboard/org/events', $this->authHeader($org));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['events', 'total']
            ]);
    }

    public function test_org_cannot_see_other_org_events()
    {
        $org1 = $this->createUser('organization');
        $org2 = $this->createUser('organization');

        $this->createOrgEvent($org1);

        $response = $this->getJson('/api/dashboard/org/events', $this->authHeader($org2));

        $this->assertEquals(0, $response->json('data.total'));
    }

    public function test_org_can_see_event_details_with_registrations()
    {
        $org = $this->createUser('organization');
        $user = $this->createUser('user');
        $event = $this->createOrgEvent($org);

        EventRegistration::create([
            'event_id' => $event->id,
            'profile_id' => $user->profile->id,
            'registration_date' => now(),
            'attendance_status' => 'registered',
        ]);

        $response = $this->getJson(
            '/api/dashboard/org/events/' . $event->id,
            $this->authHeader($org)
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['event', 'registrations']
            ]);
    }

    public function test_org_can_see_stats()
    {
        $org = $this->createUser('organization');

        $response = $this->getJson('/api/dashboard/org/stats', $this->authHeader($org));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_events',
                    'total_donors',
                    'attendance_rate',
                ]
            ]);
    }

    public function test_user_cannot_access_org_dashboard()
    {
        $user = $this->createUser('user');
        $response = $this->getJson('/api/dashboard/org/events', $this->authHeader($user));

        $response->assertStatus(403);
    }
}
