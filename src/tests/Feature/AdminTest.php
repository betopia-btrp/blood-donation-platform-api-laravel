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

    public function test_admin_can_filter_users_by_role()
    {
        $admin = $this->createUser('admin');
        $this->createUser('user');
        $this->createUser('organization');

        $response = $this->getJson('/api/admin/users?role=user', $this->authHeader($admin));

        $response->assertStatus(200);
        foreach ($response->json('data.users') as $user) {
            $this->assertEquals('user', $user['role']['name']);
        }
    }

    public function test_admin_can_filter_users_by_active_status()
    {
        $admin = $this->createUser('admin');
        $this->createUser('user', true);
        $this->createUser('user', false);

        $response = $this->getJson('/api/admin/users?is_active=0', $this->authHeader($admin));

        $response->assertStatus(200);
        foreach ($response->json('data.users') as $user) {
            $this->assertFalse($user['is_active']);
        }
    }

    public function test_admin_can_search_users_by_name_or_email()
    {
        $admin = $this->createUser('admin');

        \App\Models\User::create([
            'name'     => 'UniqueSearchableName',
            'email'    => 'unique_search@example.com',
            'password' => bcrypt('password123'),
            'role_id'  => \App\Models\Role::where('name', 'user')->first()->id,
        ]);

        $response = $this->getJson('/api/admin/users?search=UniqueSearchable', $this->authHeader($admin));

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response->json('data.total'));
    }

    public function test_admin_can_filter_events_by_status()
    {
        $admin = $this->createUser('admin');
        $org   = $this->createUser('organization');

        Event::create([
            'organization_id' => $org->organization->id,
            'title'           => 'Pending Event',
            'event_date'      => now()->addDays(7),
            'location'        => 'Dhaka',
            'district'        => 'Dhaka',
            'division'        => 'Dhaka',
            'max_capacity'    => 50,
            'status'          => 'pending',
        ]);

        $response = $this->getJson('/api/admin/events?status=pending', $this->authHeader($admin));

        $response->assertStatus(200);
        foreach ($response->json('data.events') as $event) {
            $this->assertEquals('pending', $event['status']);
        }
    }

    public function test_admin_can_filter_donation_requests_by_status()
    {
        $admin     = $this->createUser('admin');
        $requester = $this->createUser('user');
        $donor     = $this->createUser('user');

        \App\Models\DonationRequest::create([
            'requester_user_id' => $requester->id,
            'blood_group'       => 'A+',
            'quantity'          => 1,
            'district'          => 'Dhaka',
            'status'            => 'open',
        ]);

        $response = $this->getJson('/api/admin/donation-requests?status=open', $this->authHeader($admin));

        $response->assertStatus(200);
        foreach ($response->json('data.requests') as $req) {
            $this->assertEquals('open', $req['status']);
        }
    }

    public function test_admin_can_filter_reports_by_status()
    {
        $admin    = $this->createUser('admin');
        $reporter = $this->createUser('user');

        \App\Models\Report::create([
            'reporter_user_id' => $reporter->id,
            'report_type'      => 'spam',
            'status'           => 'pending',
        ]);

        \App\Models\Report::create([
            'reporter_user_id' => $reporter->id,
            'report_type'      => 'fake',
            'status'           => 'resolved',
        ]);

        $response = $this->getJson('/api/admin/reports?status=pending', $this->authHeader($admin));

        $response->assertStatus(200);
        foreach ($response->json('data.reports') as $report) {
            $this->assertEquals('pending', $report['status']);
        }
    }
}
