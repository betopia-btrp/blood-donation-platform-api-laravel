<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\DonationRequestRecipient;

class DonorActionTest extends TestCase
{
    private function setupRequestAndRecipient()
    {
        $requester = $this->createUser('user');
        $donor = $this->createUser('user');

        $this->postJson('/api/donation-requests', [
            'blood_group' => 'A+',
            'quantity' => 1,
            'district' => 'Dhaka',
            'donor_ids' => [$donor->profile->id],
        ], $this->authHeader($requester));

        $recipient = DonationRequestRecipient::where('donor_profile_id', $donor->profile->id)->first();

        return [$donor, $recipient];
    }

    public function test_donor_can_see_incoming_requests()
    {
        [$donor] = $this->setupRequestAndRecipient();

        $response = $this->getJson('/api/my/incoming-requests', $this->authHeader($donor));

        $response->assertStatus(200);
    }

    public function test_donor_can_accept_request()
    {
        [$donor, $recipient] = $this->setupRequestAndRecipient();

        $response = $this->postJson(
            '/api/my/incoming-requests/' . $recipient->id . '/accept',
            [],
            $this->authHeader($donor)
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('donation_request_recipients', [
            'id' => $recipient->id,
            'response_status' => 'accepted',
        ]);
    }

    public function test_donor_can_reject_request()
    {
        [$donor, $recipient] = $this->setupRequestAndRecipient();

        $response = $this->postJson(
            '/api/my/incoming-requests/' . $recipient->id . '/reject',
            [],
            $this->authHeader($donor)
        );

        $response->assertStatus(200);
    }

    public function test_donor_cannot_respond_twice()
    {
        [$donor, $recipient] = $this->setupRequestAndRecipient();

        $this->postJson(
            '/api/my/incoming-requests/' . $recipient->id . '/accept',
            [],
            $this->authHeader($donor)
        );

        $response = $this->postJson(
            '/api/my/incoming-requests/' . $recipient->id . '/reject',
            [],
            $this->authHeader($donor)
        );

        $response->assertStatus(400);
    }

    public function test_confirm_donated_increases_trust_score()
    {
        [$donor, $recipient] = $this->setupRequestAndRecipient();

        $donor->profile->trust_score = 0.80;
        $donor->profile->save();

        $this->postJson(
            '/api/my/incoming-requests/' . $recipient->id . '/accept',
            [],
            $this->authHeader($donor)
        );

        $recipient->update(['requester_confirmed' => true]);

        $this->postJson(
            '/api/my/incoming-requests/' . $recipient->id . '/confirm-donated',
            [],
            $this->authHeader($donor)
        );

        $donor->profile->refresh();
        $this->assertEquals(0.85, $donor->profile->trust_score);
    }

    public function test_trust_score_cannot_exceed_max()
    {
        [$donor, $recipient] = $this->setupRequestAndRecipient();

        $donor->profile->trust_score = 1.00;
        $donor->profile->save();

        $this->postJson(
            '/api/my/incoming-requests/' . $recipient->id . '/accept',
            [],
            $this->authHeader($donor)
        );

        $recipient->update(['requester_confirmed' => true]);

        $this->postJson(
            '/api/my/incoming-requests/' . $recipient->id . '/confirm-donated',
            [],
            $this->authHeader($donor)
        );

        $donor->profile->refresh();
        $this->assertEquals(1.00, $donor->profile->trust_score);
    }

    public function test_trust_score_does_not_update_on_donor_confirm_alone()
    {
        [$donor, $recipient] = $this->setupRequestAndRecipient();

        $donor->profile->trust_score = 0.80;
        $donor->profile->save();

        $this->postJson(
            '/api/my/incoming-requests/' . $recipient->id . '/accept',
            [],
            $this->authHeader($donor)
        );

        $this->postJson(
            '/api/my/incoming-requests/' . $recipient->id . '/confirm-donated',
            [],
            $this->authHeader($donor)
        );

        $donor->profile->refresh();
        $this->assertEquals(0.80, $donor->profile->trust_score);

        $this->assertDatabaseMissing('donation_request_recipients', [
            'id'              => $recipient->id,
            'response_status' => 'donated',
        ]);
    }

    public function test_donor_cannot_accept_within_120_days_of_last_donation()
    {
        [$donor, $recipient] = $this->setupRequestAndRecipient();

        $donor->profile->last_donation_date = now()->subDays(30);
        $donor->profile->save();

        $response = $this->postJson(
            '/api/my/incoming-requests/' . $recipient->id . '/accept',
            [],
            $this->authHeader($donor)
        );

        $response->assertStatus(400);
    }

    public function test_donor_can_accept_after_120_days()
    {
        [$donor, $recipient] = $this->setupRequestAndRecipient();

        $donor->profile->last_donation_date = now()->subDays(121);
        $donor->profile->save();

        $response = $this->postJson(
            '/api/my/incoming-requests/' . $recipient->id . '/accept',
            [],
            $this->authHeader($donor)
        );

        $response->assertStatus(200);
    }

    public function test_donor_cannot_accept_two_concurrent_requests()
    {
        $requester1 = $this->createUser('user');
        $requester2 = $this->createUser('user');
        $donor      = $this->createUser('user');

        $this->postJson('/api/donation-requests', [
            'blood_group' => 'A+',
            'quantity'    => 1,
            'district'    => 'Dhaka',
            'donor_ids'   => [$donor->profile->id],
        ], $this->authHeader($requester1));

        $this->postJson('/api/donation-requests', [
            'blood_group' => 'A+',
            'quantity'    => 1,
            'district'    => 'Dhaka',
            'donor_ids'   => [$donor->profile->id],
        ], $this->authHeader($requester2));

        $recipients = \App\Models\DonationRequestRecipient::where('donor_profile_id', $donor->profile->id)
            ->orderBy('id')
            ->get();

        $this->postJson(
            '/api/my/incoming-requests/' . $recipients[0]->id . '/accept',
            [],
            $this->authHeader($donor)
        )->assertStatus(200);

        $response = $this->postJson(
            '/api/my/incoming-requests/' . $recipients[1]->id . '/accept',
            [],
            $this->authHeader($donor)
        );

        $response->assertStatus(400);
    }
}
