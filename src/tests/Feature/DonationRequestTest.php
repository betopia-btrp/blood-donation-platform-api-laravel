<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\DonationRequestRecipient;
use App\Models\Payment;

class DonationRequestTest extends TestCase
{
    private function createRequest($requester, $donors)
    {
        $donorIds = collect($donors)->map(fn($d) => $d->profile->id)->toArray();

        return $this->postJson('/api/donation-requests', [
            'blood_group' => 'A+',
            'quantity' => 2,
            'hospital_name' => 'Dhaka Medical',
            'district' => 'Dhaka',
            'donor_ids' => $donorIds,
        ], $this->authHeader($requester));
    }

    public function test_user_can_create_donation_request()
    {
        $requester = $this->createUser('user');
        $donor = $this->createUser('user');

        $response = $this->createRequest($requester, [$donor]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['request', 'sent_to']
            ]);
    }

    public function test_request_sent_to_selected_donors()
    {
        $requester = $this->createUser('user');
        $donor = $this->createUser('user');

        $this->createRequest($requester, [$donor]);

        $this->assertDatabaseHas('donation_request_recipients', [
            'donor_profile_id' => $donor->profile->id,
        ]);
    }

    public function test_requester_can_see_request_details()
    {
        $requester = $this->createUser('user');
        $donor = $this->createUser('user');

        $res = $this->createRequest($requester, [$donor]);
        $id = $res->json('data.request.id');

        $response = $this->getJson('/api/donation-requests/' . $id, $this->authHeader($requester));

        $response->assertStatus(200);
    }

    public function test_other_user_cannot_see_request_details()
    {
        $requester = $this->createUser('user');
        $donor = $this->createUser('user');
        $other = $this->createUser('user');

        $res = $this->createRequest($requester, [$donor]);
        $id = $res->json('data.request.id');

        $response = $this->getJson('/api/donation-requests/' . $id, $this->authHeader($other));

        $response->assertStatus(403);
    }

    public function test_requester_can_cancel_open_request()
    {
        $requester = $this->createUser('user');
        $donor = $this->createUser('user');

        $res = $this->createRequest($requester, [$donor]);
        $id = $res->json('data.request.id');

        $response = $this->deleteJson('/api/donation-requests/' . $id, [], $this->authHeader($requester));

        $response->assertStatus(200);
    }

    public function test_cannot_confirm_payment_without_accepted_donor()
    {
        $requester = $this->createUser('user');
        $donor = $this->createUser('user');

        $res = $this->createRequest($requester, [$donor]);
        $id = $res->json('data.request.id');

        $response = $this->postJson(
            '/api/donation-requests/' . $id . '/confirm-payment',
            [],
            $this->authHeader($requester)
        );

        $response->assertStatus(400);
    }

    public function test_donors_revealed_after_payment()
    {
        $requester = $this->createUser('user');
        $donor = $this->createUser('user');

        $res = $this->createRequest($requester, [$donor]);
        $id = $res->json('data.request.id');

        DonationRequestRecipient::where('request_id', $id)->update([
            'response_status' => 'accepted',
        ]);

        Payment::create([
            'donation_request_id' => $id,
            'payer_user_id' => $requester->id,
            'amount' => 0,
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $response = $this->getJson(
            '/api/donation-requests/' . $id . '/donors-revealed',
            $this->authHeader($requester)
        );

        $response->assertStatus(200);
    }

    public function test_cannot_complete_without_payment()
    {
        $requester = $this->createUser('user');
        $donor = $this->createUser('user');

        $res = $this->createRequest($requester, [$donor]);
        $id = $res->json('data.request.id');

        DonationRequestRecipient::where('request_id', $id)->update([
            'response_status' => 'accepted',
        ]);

        $response = $this->postJson(
            '/api/donation-requests/' . $id . '/complete',
            [],
            $this->authHeader($requester)
        );

        $response->assertStatus(400);
    }

    public function test_completing_request_penalizes_accepted_but_not_donated_donors()
    {
        $requester = $this->createUser('user');
        $donor     = $this->createUser('user');

        $res = $this->createRequest($requester, [$donor]);
        $id  = $res->json('data.request.id');

        $donor->profile->trust_score = 0.80;
        $donor->profile->save();

        DonationRequestRecipient::where('request_id', $id)->update([
            'response_status' => 'accepted',
        ]);

        Payment::create([
            'donation_request_id' => $id,
            'payer_user_id'       => $requester->id,
            'amount'              => 0,
            'status'              => 'confirmed',
            'confirmed_at'        => now(),
        ]);

        $this->postJson(
            '/api/donation-requests/' . $id . '/complete',
            [],
            $this->authHeader($requester)
        )->assertStatus(200);

        $donor->profile->refresh();
        $this->assertEquals(0.70, $donor->profile->trust_score);
    }

    public function test_completing_request_does_not_penalize_donated_donors()
    {
        $requester = $this->createUser('user');
        $donor     = $this->createUser('user');

        $res = $this->createRequest($requester, [$donor]);
        $id  = $res->json('data.request.id');

        $donor->profile->trust_score = 0.80;
        $donor->profile->save();

        DonationRequestRecipient::where('request_id', $id)->update([
            'response_status' => 'donated',
        ]);

        Payment::create([
            'donation_request_id' => $id,
            'payer_user_id'       => $requester->id,
            'amount'              => 0,
            'status'              => 'confirmed',
            'confirmed_at'        => now(),
        ]);

        $this->postJson(
            '/api/donation-requests/' . $id . '/complete',
            [],
            $this->authHeader($requester)
        )->assertStatus(200);

        $donor->profile->refresh();
        $this->assertEquals(0.80, $donor->profile->trust_score);
    }
}
