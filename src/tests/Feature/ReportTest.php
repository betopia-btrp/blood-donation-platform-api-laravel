<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Report;
use App\Models\Event;

class ReportTest extends TestCase
{
    private function createApprovedEvent($org)
    {
        return Event::create([
            'organization_id' => $org->organization->id,
            'title'           => 'Test Event',
            'event_date'      => now()->addDays(7),
            'location'        => 'Dhaka',
            'district'        => 'Dhaka',
            'division'        => 'Dhaka',
            'max_capacity'    => 50,
            'status'          => 'upcoming',
        ]);
    }

    private function createDonationRequest($requester, $donor)
    {
        $response = $this->postJson('/api/donation-requests', [
            'blood_group' => 'A+',
            'quantity'    => 1,
            'district'    => 'Dhaka',
            'donor_ids'   => [$donor->profile->id],
        ], $this->authHeader($requester));

        return $response->json('data.request.id');
    }

    // --- Donation Request Reports ---

    public function test_user_can_report_donation_request()
    {
        $requester = $this->createUser('user');
        $donor     = $this->createUser('user');
        $reporter  = $this->createUser('user');

        $requestId = $this->createDonationRequest($requester, $donor);

        $response = $this->postJson(
            '/api/donation-requests/' . $requestId . '/report',
            ['report_type' => 'spam', 'reason' => 'Looks fake'],
            $this->authHeader($reporter)
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('reports', [
            'reporter_user_id'           => $reporter->id,
            'target_donation_request_id' => $requestId,
            'report_type'                => 'spam',
            'status'                     => 'pending',
        ]);
    }

    public function test_report_donation_request_returns_404_for_missing_request()
    {
        $user = $this->createUser('user');

        $response = $this->postJson(
            '/api/donation-requests/99999/report',
            [],
            $this->authHeader($user)
        );

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_report_donation_request()
    {
        $requester = $this->createUser('user');
        $donor     = $this->createUser('user');

        $requestId = $this->createDonationRequest($requester, $donor);

        $response = $this->postJson('/api/donation-requests/' . $requestId . '/report', []);

        $response->assertStatus(401);
    }

    // --- Event Reports ---

    public function test_user_can_report_event()
    {
        $org      = $this->createUser('organization');
        $reporter = $this->createUser('user');

        $event = $this->createApprovedEvent($org);

        $response = $this->postJson(
            '/api/events/' . $event->id . '/report',
            ['report_type' => 'fake', 'reason' => 'Suspicious event'],
            $this->authHeader($reporter)
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('reports', [
            'reporter_user_id' => $reporter->id,
            'target_event_id'  => $event->id,
            'report_type'      => 'fake',
            'status'           => 'pending',
        ]);
    }

    public function test_report_event_returns_404_for_missing_event()
    {
        $user = $this->createUser('user');

        $response = $this->postJson(
            '/api/events/99999/report',
            [],
            $this->authHeader($user)
        );

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_report_event()
    {
        $org   = $this->createUser('organization');
        $event = $this->createApprovedEvent($org);

        $response = $this->postJson('/api/events/' . $event->id . '/report', []);

        $response->assertStatus(401);
    }

    // --- Admin Report Management ---

    public function test_admin_can_list_reports()
    {
        $admin    = $this->createUser('admin');
        $reporter = $this->createUser('user');

        Report::create([
            'reporter_user_id'           => $reporter->id,
            'target_donation_request_id' => null,
            'report_type'                => 'spam',
            'status'                     => 'pending',
        ]);

        $response = $this->getJson('/api/admin/reports', $this->authHeader($admin));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['reports', 'current_page', 'last_page', 'total'],
            ]);
    }

    public function test_admin_can_view_report_details()
    {
        $admin    = $this->createUser('admin');
        $reporter = $this->createUser('user');

        $report = Report::create([
            'reporter_user_id' => $reporter->id,
            'report_type'      => 'fake',
            'status'           => 'pending',
        ]);

        $response = $this->getJson('/api/admin/reports/' . $report->id, $this->authHeader($admin));

        $response->assertStatus(200);
    }

    public function test_admin_can_review_report()
    {
        $admin    = $this->createUser('admin');
        $reporter = $this->createUser('user');

        $report = Report::create([
            'reporter_user_id' => $reporter->id,
            'report_type'      => 'abusive',
            'status'           => 'pending',
        ]);

        $response = $this->putJson(
            '/api/admin/reports/' . $report->id . '/review',
            [],
            $this->authHeader($admin)
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('reports', [
            'id'     => $report->id,
            'status' => 'reviewed',
        ]);
    }

    public function test_admin_can_resolve_report()
    {
        $admin    = $this->createUser('admin');
        $reporter = $this->createUser('user');

        $report = Report::create([
            'reporter_user_id' => $reporter->id,
            'report_type'      => 'spam',
            'status'           => 'pending',
        ]);

        $response = $this->putJson(
            '/api/admin/reports/' . $report->id . '/resolve',
            [],
            $this->authHeader($admin)
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('reports', [
            'id'     => $report->id,
            'status' => 'resolved',
        ]);
    }

    public function test_admin_cannot_review_already_actioned_report()
    {
        $admin    = $this->createUser('admin');
        $reporter = $this->createUser('user');

        $report = Report::create([
            'reporter_user_id' => $reporter->id,
            'report_type'      => 'spam',
            'status'           => 'resolved',
        ]);

        $response = $this->putJson(
            '/api/admin/reports/' . $report->id . '/review',
            [],
            $this->authHeader($admin)
        );

        $response->assertStatus(400);
    }

    public function test_non_admin_cannot_access_report_management()
    {
        $user = $this->createUser('user');

        $response = $this->getJson('/api/admin/reports', $this->authHeader($user));

        $response->assertStatus(403);
    }

    // --- User Reports ---

    public function test_user_can_report_another_user()
    {
        $reporter = $this->createUser('user');
        $target   = $this->createUser('user');

        $response = $this->postJson(
            '/api/users/' . $target->id . '/report',
            ['report_type' => 'abusive', 'reason' => 'Bad behaviour'],
            $this->authHeader($reporter)
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('reports', [
            'reporter_user_id' => $reporter->id,
            'target_user_id'   => $target->id,
            'report_type'      => 'abusive',
            'status'           => 'pending',
        ]);
    }

    public function test_user_cannot_report_themselves()
    {
        $user = $this->createUser('user');

        $response = $this->postJson(
            '/api/users/' . $user->id . '/report',
            ['report_type' => 'spam'],
            $this->authHeader($user)
        );

        $response->assertStatus(400);
    }

    public function test_report_user_returns_404_for_missing_user()
    {
        $user = $this->createUser('user');

        $response = $this->postJson(
            '/api/users/99999/report',
            [],
            $this->authHeader($user)
        );

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_report_user()
    {
        $target = $this->createUser('user');

        $response = $this->postJson('/api/users/' . $target->id . '/report', []);

        $response->assertStatus(401);
    }

    // --- Admin sees related data ---

    public function test_admin_show_report_includes_donation_request_data()
    {
        $admin     = $this->createUser('admin');
        $requester = $this->createUser('user');
        $donor     = $this->createUser('user');

        $requestId = $this->createDonationRequest($requester, $donor);

        $report = Report::create([
            'reporter_user_id'           => $requester->id,
            'target_donation_request_id' => $requestId,
            'report_type'                => 'fake',
            'status'                     => 'pending',
        ]);

        $response = $this->getJson('/api/admin/reports/' . $report->id, $this->authHeader($admin));

        $response->assertStatus(200)
            ->assertJsonPath('data.target_donation_request_id', $requestId)
            ->assertJsonStructure(['data' => ['target_donation_request']]);
    }

    public function test_admin_show_report_includes_event_data()
    {
        $admin    = $this->createUser('admin');
        $org      = $this->createUser('organization');
        $reporter = $this->createUser('user');

        $event = $this->createApprovedEvent($org);

        $report = Report::create([
            'reporter_user_id' => $reporter->id,
            'target_event_id'  => $event->id,
            'report_type'      => 'spam',
            'status'           => 'pending',
        ]);

        $response = $this->getJson('/api/admin/reports/' . $report->id, $this->authHeader($admin));

        $response->assertStatus(200)
            ->assertJsonPath('data.target_event_id', $event->id)
            ->assertJsonStructure(['data' => ['target_event']]);
    }

    public function test_admin_show_report_includes_target_user_data()
    {
        $admin    = $this->createUser('admin');
        $reporter = $this->createUser('user');
        $target   = $this->createUser('user');

        $report = Report::create([
            'reporter_user_id' => $reporter->id,
            'target_user_id'   => $target->id,
            'report_type'      => 'abusive',
            'status'           => 'pending',
        ]);

        $response = $this->getJson('/api/admin/reports/' . $report->id, $this->authHeader($admin));

        $response->assertStatus(200)
            ->assertJsonPath('data.target_user_id', $target->id)
            ->assertJsonStructure(['data' => ['target_user']]);
    }

    // --- nullOnDelete behaviour ---

    public function test_deleting_donation_request_nullifies_report_fk()
    {
        $admin     = $this->createUser('admin');
        $requester = $this->createUser('user');
        $donor     = $this->createUser('user');

        $requestId = $this->createDonationRequest($requester, $donor);

        $report = Report::create([
            'reporter_user_id'           => $requester->id,
            'target_donation_request_id' => $requestId,
            'report_type'                => 'fake',
            'status'                     => 'pending',
        ]);

        \App\Models\DonationRequest::find($requestId)->forceDelete();

        $report->refresh();
        $this->assertNull($report->target_donation_request_id);
    }

    public function test_deleting_event_nullifies_report_fk()
    {
        $org      = $this->createUser('organization');
        $reporter = $this->createUser('user');

        $event = $this->createApprovedEvent($org);

        $report = Report::create([
            'reporter_user_id' => $reporter->id,
            'target_event_id'  => $event->id,
            'report_type'      => 'spam',
            'status'           => 'pending',
        ]);

        $event->forceDelete();

        $report->refresh();
        $this->assertNull($report->target_event_id);
    }
}
