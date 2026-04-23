<?php

namespace Tests\Feature;

use Tests\TestCase;

class DonorDiscoveryTest extends TestCase
{
    public function test_guest_can_see_donors()
    {
        $this->createUser('user');

        $response = $this->getJson('/api/donors');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['donors', 'total']
            ]);
    }

    public function test_authenticated_user_sees_viewer_context()
    {
        $user = $this->createUser('user');

        $response = $this->getJson('/api/donors', $this->authHeader($user));

        $response->assertStatus(200);

        $donors = $response->json('data.donors');
        if (count($donors) > 0) {
            $this->assertArrayHasKey('is_requested', $donors[0]);
        }
    }

    public function test_current_user_excluded_from_donors()
    {
        $user = $this->createUser('user');

        $response = $this->getJson('/api/donors', $this->authHeader($user));

        $donors = $response->json('data.donors');
        $ids = array_column($donors, 'user_id');

        $this->assertNotContains($user->id, $ids);
    }

    public function test_filter_by_blood_group()
    {
        $response = $this->getJson('/api/donors?blood_group=A%2B');
        $response->assertStatus(200);
    }

    public function test_donor_public_profile()
    {
        $donor = $this->createUser('user');
        $profile = $donor->profile;

        $response = $this->getJson('/api/donors/' . $profile->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'blood_group', 'trust_score']
            ]);
    }

    public function test_donor_profile_hides_contact_info()
    {
        $donor = $this->createUser('user');
        $profile = $donor->profile;

        $response = $this->getJson('/api/donors/' . $profile->id);

        $data = $response->json('data');
        $this->assertArrayNotHasKey('email', $data);
    }
}
