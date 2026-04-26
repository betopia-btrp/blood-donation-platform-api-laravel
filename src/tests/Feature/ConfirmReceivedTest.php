<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\DonationRequestRecipient;

class ConfirmReceivedTest extends TestCase
{
    private function setupAcceptedRecipient()
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

        $recipient->update([
            'response_status' => 'accepted',
            'responded_at'    => now(),
        ]);

        return [$requester, $donor, $recipient];
    }

    public function test_requester_can_confirm_received()
    {
        [$requester, $donor, $recipient] = $this->setupAcceptedRecipient();

        $response = $this->postJson(
            '/api/donation-requests/' . $recipient->request_id . '/confirm-received/' . $donor->profile->id,
            [],
            $this->authHeader($requester)
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('donation_request_recipients', [
            'id'                  => $recipient->id,
            'requester_confirmed' => true,
        ]);
    }

    public function test_non_owner_cannot_confirm_received()
    {
        [$requester, $donor, $recipient] = $this->setupAcceptedRecipient();

        $other = $this->createUser('user');

        $response = $this->postJson(
            '/api/donation-requests/' . $recipient->request_id . '/confirm-received/' . $donor->profile->id,
            [],
            $this->authHeader($other)
        );

        $response->assertStatus(403);
    }

    public function test_requester_cannot_confirm_received_twice()
    {
        [$requester, $donor, $recipient] = $this->setupAcceptedRecipient();

        $url = '/api/donation-requests/' . $recipient->request_id . '/confirm-received/' . $donor->profile->id;

        $this->postJson($url, [], $this->authHeader($requester));

        $response = $this->postJson($url, [], $this->authHeader($requester));

        $response->assertStatus(400);
    }

    public function test_both_confirm_updates_trust_score_and_status()
    {
        [$requester, $donor, $recipient] = $this->setupAcceptedRecipient();

        $donor->profile->trust_score = 0.80;
        $donor->profile->save();

        $recipient->update(['donor_confirmed' => true]);

        $this->postJson(
            '/api/donation-requests/' . $recipient->request_id . '/confirm-received/' . $donor->profile->id,
            [],
            $this->authHeader($requester)
        );

        $donor->profile->refresh();
        $this->assertEquals(0.85, $donor->profile->trust_score);

        $this->assertDatabaseHas('donation_request_recipients', [
            'id'              => $recipient->id,
            'response_status' => 'donated',
        ]);
    }

    public function test_trust_score_capped_at_max_on_confirm_received()
    {
        [$requester, $donor, $recipient] = $this->setupAcceptedRecipient();

        $donor->profile->trust_score = 1.00;
        $donor->profile->save();

        $recipient->update(['donor_confirmed' => true]);

        $this->postJson(
            '/api/donation-requests/' . $recipient->request_id . '/confirm-received/' . $donor->profile->id,
            [],
            $this->authHeader($requester)
        );

        $donor->profile->refresh();
        $this->assertEquals(1.00, $donor->profile->trust_score);
    }

    public function test_trust_score_does_not_update_on_requester_confirm_alone()
    {
        [$requester, $donor, $recipient] = $this->setupAcceptedRecipient();

        $donor->profile->trust_score = 0.80;
        $donor->profile->save();

        $this->postJson(
            '/api/donation-requests/' . $recipient->request_id . '/confirm-received/' . $donor->profile->id,
            [],
            $this->authHeader($requester)
        );

        $donor->profile->refresh();
        $this->assertEquals(0.80, $donor->profile->trust_score);

        $this->assertDatabaseMissing('donation_request_recipients', [
            'id'              => $recipient->id,
            'response_status' => 'donated',
        ]);
    }
}
