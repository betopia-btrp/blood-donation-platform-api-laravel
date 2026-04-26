<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\DonationRequest;
use App\Models\DonationRequestRecipient;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Payment;

class UserDashboardTest extends TestCase
{
    public function test_user_can_see_own_requests()
    {
        $user = $this->createUser('user');
        $donor = $this->createUser('user');

        DonationRequest::create([
            'requester_user_id' => $user->id,
            'blood_group' => 'A+',
            'quantity' => 1,
            'district' => 'Dhaka',
            'status' => 'open',
        ]);

        $response = $this->getJson('/api/dashboard/my-requests', $this->authHeader($user));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['requests', 'total']
            ]);
    }

    public function test_user_can_see_request_details()
    {
        $user = $this->createUser('user');
        $donor = $this->createUser('user');

        $request = DonationRequest::create([
            'requester_user_id' => $user->id,
            'blood_group' => 'A+',
            'quantity' => 1,
            'district' => 'Dhaka',
            'status' => 'open',
        ]);

        $response = $this->getJson(
            '/api/dashboard/my-requests/' . $request->id,
            $this->authHeader($user)
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['request', 'recipient_stats', 'payment_confirmed']
            ]);
    }

    public function test_payment_confirmed_false_before_payment()
    {
        $user = $this->createUser('user');

        $request = DonationRequest::create([
            'requester_user_id' => $user->id,
            'blood_group' => 'A+',
            'quantity' => 1,
            'district' => 'Dhaka',
            'status' => 'open',
        ]);

        $response = $this->getJson(
            '/api/dashboard/my-requests/' . $request->id,
            $this->authHeader($user)
        );

        $this->assertFalse($response->json('data.payment_confirmed'));
        $this->assertNull($response->json('data.accepted_donors'));
    }

    public function test_donors_revealed_after_payment_confirmed()
    {
        $user = $this->createUser('user');
        $donor = $this->createUser('user');

        $request = DonationRequest::create([
            'requester_user_id' => $user->id,
            'blood_group' => 'A+',
            'quantity' => 1,
            'district' => 'Dhaka',
            'status' => 'open',
        ]);

        DonationRequestRecipient::create([
            'request_id' => $request->id,
            'donor_profile_id' => $donor->profile->id,
            'response_status' => 'accepted',
            'sent_at' => now(),
        ]);

        Payment::create([
            'donation_request_id' => $request->id,
            'payer_user_id' => $user->id,
            'amount' => 0,
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $response = $this->getJson(
            '/api/dashboard/my-requests/' . $request->id,
            $this->authHeader($user)
        );

        $this->assertTrue($response->json('data.payment_confirmed'));
        $this->assertNotNull($response->json('data.accepted_donors'));
    }

    public function test_user_can_see_incoming_requests()
    {
        $requester = $this->createUser('user');
        $donor = $this->createUser('user');

        $request = DonationRequest::create([
            'requester_user_id' => $requester->id,
            'blood_group' => 'A+',
            'quantity' => 1,
            'district' => 'Dhaka',
            'status' => 'open',
        ]);

        DonationRequestRecipient::create([
            'request_id' => $request->id,
            'donor_profile_id' => $donor->profile->id,
            'response_status' => 'pending',
            'sent_at' => now(),
        ]);

        $response = $this->getJson('/api/my/incoming-requests', $this->authHeader($donor));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['requests', 'total']
            ]);
    }

    public function test_user_can_see_registered_events()
    {
        $user = $this->createUser('user');
        $org = $this->createUser('organization');

        $event = Event::create([
            'organization_id' => $org->organization->id,
            'title' => 'Blood Drive',
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

        $response = $this->getJson('/api/dashboard/my-events', $this->authHeader($user));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['events', 'total']
            ]);
    }

    public function test_user_can_see_donation_history()
    {
        $user = $this->createUser('user');

        $response = $this->getJson('/api/dashboard/my-donations', $this->authHeader($user));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['donations', 'total']
            ]);
    }

    public function test_user_can_see_stats()
    {
        $user = $this->createUser('user');

        $response = $this->getJson('/api/dashboard/stats', $this->authHeader($user));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'trust_score',
                    'total_donations',
                    'total_requests',
                    'success_rate',
                ]
            ]);
    }
}
