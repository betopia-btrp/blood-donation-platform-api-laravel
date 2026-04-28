<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Organization;
use App\Models\OrganizationDocument;
use App\Models\Event;
use App\Models\DonationRequest;
use App\Models\DonationRequestRecipient;
use App\Models\EventRegistration;
use App\Models\Payment;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $this->call([
            AdminSeeder::class,
        ]);

        // Donors (20 users)
        $donors = User::factory(50)->create();
        $donorProfiles = $donors->map(function ($user) {
            return UserProfile::factory()->create(['user_id' => $user->id]);
        });

        // Organizations (5 orgs)
        $orgs = User::factory(5)->organization()->create();
        $orgProfiles = $orgs->map(function ($user) {
            $org = Organization::factory()->create([
                'user_id' => $user->id,
                'org_name' => $user->name . ' Foundation',
            ]);

            OrganizationDocument::factory(2)->create([
                'organization_id' => $org->id,
            ]);

            return $org;
        });

        // Events (3 events per org)
        $events = collect();
        $orgProfiles->each(function ($org) use (&$events) {
            $orgEvents = Event::factory(3)->create([
                'organization_id' => $org->id,
                'status' => 'upcoming',
            ]);
            $events = $events->merge($orgEvents);
        });

        // Event Registrations (10 users per event)
        $events->each(function ($event) use ($donorProfiles) {
            $selectedProfiles = $donorProfiles->random(min(10, $donorProfiles->count()));
            $selectedProfiles->each(function ($profile) use ($event) {
                // Avoid duplicate registrations
                $exists = EventRegistration::where('event_id', $event->id)
                    ->where('profile_id', $profile->id)
                    ->exists();

                if (!$exists) {
                    EventRegistration::factory()->create([
                        'event_id' => $event->id,
                        'profile_id' => $profile->id,
                    ]);
                }
            });
        });

        // Donation Requests (15 requests)
        $requests = collect();
        for ($i = 0; $i < 15; $i++) {
            $requester = $donors->random();
            $request = DonationRequest::factory()->create([
                'requester_user_id' => $requester->id,
            ]);
            $requests->push($request);

            // 3-5 donors per request
            $selectedDonors = $donorProfiles
                ->filter(fn($p) => $p->user_id !== $requester->id)
                ->random(rand(3, 5));

            $selectedDonors->each(function ($profile) use ($request) {
                DonationRequestRecipient::create([
                    'request_id' => $request->id,
                    'donor_profile_id' => $profile->id,
                    'response_status' => fake()->randomElement(['pending', 'accepted', 'rejected', 'donated']),
                    'sent_at' => now(),
                    'responded_at' => now(),
                ]);
            });

            // Some requests have payments
            $acceptedCount = DonationRequestRecipient::where('request_id', $request->id)
                ->whereIn('response_status', ['accepted', 'donated'])
                ->count();

            if ($acceptedCount > 0 && rand(0, 1)) {
                Payment::create([
                    'donation_request_id' => $request->id,
                    'payer_user_id' => $requester->id,
                    'amount' => 0,
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                ]);
            }
        }
    }
}
