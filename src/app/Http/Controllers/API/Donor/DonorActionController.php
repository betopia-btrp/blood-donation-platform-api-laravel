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

        $recipient->update([
            'response_status' => 'donated',
            'responded_at'    => now(),
        ]);

        $profile->trust_score = min(1.00, $profile->trust_score + 0.05);
        $profile->save();

        $profile->update(['last_donation_date' => now()]);

        return $this->success([
            'trust_score' => $profile->trust_score,
        ], 'Donation confirmed. Trust score updated.');
    }
}
