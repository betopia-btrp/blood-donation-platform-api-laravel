<?php

namespace App\Http\Controllers\API\Donor;

use App\Http\Controllers\Controller;
use App\Models\DonationRequest;
use App\Models\DonationRequestRecipient;
use App\Traits\ApiResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class DonorActionController extends Controller
{
    use ApiResponse;

    private function getUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return null;
        }
    }

    // Incoming Requests List
    public function incomingRequests()
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $profile = $user->profile;
        if (!$profile) return $this->error('Donor profile not found', 404);

        $requests = DonationRequestRecipient::with([
            'donationRequest:id,blood_group,quantity,hospital_name,district,division,status,needed_at,note',
        ])
            ->where('donor_profile_id', $profile->id)
            ->orderBy('sent_at', 'desc')
            ->paginate(20);

        return $this->success([
            'requests'     => $requests->items(),
            'current_page' => $requests->currentPage(),
            'last_page'    => $requests->lastPage(),
            'total'        => $requests->total(),
        ], 'Incoming requests retrieved');
    }

    // Single Request Detail
    public function incomingRequestShow($id)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $profile = $user->profile;
        if (!$profile) return $this->error('Donor profile not found', 404);

        $recipient = DonationRequestRecipient::with([
            'donationRequest:id,blood_group,quantity,hospital_name,district,division,status,needed_at,note',
        ])
            ->where('id', $id)
            ->where('donor_profile_id', $profile->id)
            ->first();

        if (!$recipient) return $this->error('Request not found', 404);

        return $this->success($recipient, 'Request details retrieved');
    }

    // Accept Donation Request
    public function accept($id)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $profile = $user->profile;
        if (!$profile) return $this->error('Donor profile not found', 404);

        if ($profile->last_donation_date &&
            \Carbon\Carbon::parse($profile->last_donation_date)->addDays(120)->isFuture()) {
            return $this->error('You cannot accept a donation request within 120 days of your last donation', 400);
        }

        $hasActiveAcceptance = DonationRequestRecipient::where('donor_profile_id', $profile->id)
            ->where('response_status', 'accepted')
            ->whereHas('donationRequest', fn($q) => $q->where('status', 'open'))
            ->exists();

        if ($hasActiveAcceptance) {
            return $this->error('You already have an active accepted donation request', 400);
        }

        $recipient = DonationRequestRecipient::where('id', $id)
            ->where('donor_profile_id', $profile->id)
            ->first();

        if (!$recipient) return $this->error('Request not found', 404);

        if ($recipient->response_status !== 'pending') {
            return $this->error('Request already responded to', 400);
        }

        if ($recipient->donationRequest->status !== 'open') {
            return $this->error('This donation request is no longer open', 400);
        }

        $recipient->update([
            'response_status' => 'accepted',
            'responded_at'    => now(),
        ]);

        return $this->success(null, 'Request accepted');
    }

    // Reject Donation Request
    public function reject($id)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $profile = $user->profile;
        if (!$profile) return $this->error('Donor profile not found', 404);

        $recipient = DonationRequestRecipient::where('id', $id)
            ->where('donor_profile_id', $profile->id)
            ->first();

        if (!$recipient) return $this->error('Request not found', 404);

        if ($recipient->response_status !== 'pending') {
            return $this->error('Request already responded to', 400);
        }

        $recipient->update([
            'response_status' => 'rejected',
            'responded_at'    => now(),
        ]);

        return $this->success(null, 'Request rejected');
    }

    // Confirm Donated
    public function confirmDonated($id)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $profile = $user->profile;
        if (!$profile) return $this->error('Donor profile not found', 404);

        $recipient = DonationRequestRecipient::where('id', $id)
            ->where('donor_profile_id', $profile->id)
            ->first();

        if (!$recipient) return $this->error('Request not found', 404);

        if ($recipient->response_status !== 'accepted') {
            return $this->error('You must accept the request before confirming donation', 400);
        }

        if ($recipient->donor_confirmed) {
            return $this->error('You have already confirmed this donation', 400);
        }

        $recipient->update([
            'donor_confirmed'    => true,
            'donor_confirmed_at' => now(),
        ]);

        $recipient->refresh();

        if ($recipient->requester_confirmed) {
            $newScore = min(1.00, (float) $profile->trust_score + 0.05);
            $profile->trust_score = $newScore;
            $profile->last_donation_date = now();
            $profile->save();
            $recipient->update(['response_status' => 'donated']);

            return $this->success([
                'trust_score' => $newScore,
            ], 'Donation confirmed by both parties. Trust score updated.');
        }

        return $this->success(null, 'Donation confirmed. Waiting for requester confirmation.');
    }
}
