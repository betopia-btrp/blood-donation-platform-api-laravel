<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Event;

class AdminTest extends TestCase
{
    public function test_admin_can_see_stats()
    {
        $admin = $this->createUser('admin');
        $response = $this->getJson('/api/admin/stats', $this->authHeader($admin));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['users', 'events', 'donation_requests', 'reports']
            ]);
    }

    public function test_non_admin_cannot_access_admin_routes()
    {
        $user = $this->createUser('user');
        $response = $this->getJson('/api/admin/stats', $this->authHeader($user));

        $response->assertStatus(403);
    }

    public function test_admin_can_list_users()
    {
        $admin = $this->createUser('admin');
        $response = $this->getJson('/api/admin/users', $this->authHeader($admin));

        $response->assertStatus(200);
    }

    public function test_admin_can_deactivate_user()
    {
        $admin = $this->createUser('admin');
        $user = $this->createUser('user');

        $response = $this->putJson(
            '/api/admin/users/' . $user->id . '/deactivate',
            [],
            $this->authHeader($admin)
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_cannot_deactivate_admin()
    {
        $admin1 = $this->createUser('admin');
        $admin2 = $this->createUser('admin');

        $response = $this->putJson(
            '/api/admin/users/' . $admin2->id . '/deactivate',
            [],
            $this->authHeader($admin1)
        );

        $response->assertStatus(403);
    }

    public function test_admin_can_approve_org()
    {
        $admin = $this->createUser('admin');
        $org = $this->createUser('organization');

        $response = $this->putJson(
            '/api/admin/users/' . $org->id . '/approve-org',
            [],
            $this->authHeader($admin)
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('organizations', [
            'user_id' => $org->id,
            'verification_status' => 'approved',
        ]);
    }

    public function test_admin_can_approve_event()
    {
        $admin = $this->createUser('admin');
        $org = $this->createUser('organization');

        $event = Event::create([
            'organization_id' => $org->organization->id,
            'title' => 'Test Event',
            'event_date' => now()->addDays(7),
            'location' => 'Dhaka',
            'district' => 'Dhaka',
            'division' => 'Dhaka',
            'max_capacity' => 50,
            'status' => 'pending',
        ]);

        $response = $this->putJson(
            '/api/admin/events/' . $event->id . '/approve',
            [],
            $this->authHeader($admin)
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => 'upcoming',
        ]);
    }
}
