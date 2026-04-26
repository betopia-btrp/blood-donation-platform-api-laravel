<?php

namespace App\Http\Controllers\API\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DonationRequest;
use App\Models\DonationRequestRecipient;
use App\Models\EventRegistration;
use App\Traits\ApiResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserDashboardController extends Controller
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

    // My Donation Requests
    public function myRequests()
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $requests = DonationRequest::where('requester_user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->success([
            'requests'     => $requests->items(),
            'current_page' => $requests->currentPage(),
            'last_page'    => $requests->lastPage(),
            'total'        => $requests->total(),
        ], 'My donation requests retrieved');
    }

    // My Request Detail
    public function myRequestShow($id)
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $request = DonationRequest::where('id', $id)
            ->where('requester_user_id', $user->id)
            ->first();

        if (!$request) return $this->error('Request not found', 404);

        $recipientStats = DonationRequestRecipient::where('request_id', $id)
            ->selectRaw('response_status, count(*) as total')
            ->groupBy('response_status')
            ->pluck('total', 'response_status');

        $payment = \App\Models\Payment::where('donation_request_id', $id)
            ->where('status', 'confirmed')
            ->first();

        $donors = null;

        if ($payment) {
            $donors = DonationRequestRecipient::with([
                'donorProfile:id,user_id,blood_group,district,trust_score',
                'donorProfile.user:id,name,email',
            ])
                ->where('request_id', $id)
                ->where('response_status', 'accepted')
                ->get()
                ->map(fn($item) => [
                    'name'        => $item->donorProfile->user->name,
                    'email'       => $item->donorProfile->user->email,
                    'blood_group' => $item->donorProfile->blood_group,
                    'district'    => $item->donorProfile->district,
                    'trust_score' => $item->donorProfile->trust_score,
                ]);
        }

        return $this->success([
            'request'          => $request,
            'recipient_stats'  => $recipientStats,
            'payment_confirmed' => (bool) $payment,
            'accepted_donors'  => $donors,
        ], 'Request details retrieved');
    }

    // My Incoming Requests
    public function incomingRequests()
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $profile = $user->profile;
        if (!$profile) return $this->error('Donor profile not found', 404);

        $requests = DonationRequestRecipient::with([
            'donationRequest:id,blood_group,quantity,hospital_name,district,status,needed_at',
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

    // My Registered Events
    public function myEvents()
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $profile = $user->profile;
        if (!$profile) return $this->error('Donor profile not found', 404);

        $events = EventRegistration::with([
            'event:id,title,event_date,location,district,status',
        ])
            ->where('profile_id', $profile->id)
            ->orderBy('registration_date', 'desc')
            ->paginate(20);

        return $this->success([
            'events'       => $events->items(),
            'current_page' => $events->currentPage(),
            'last_page'    => $events->lastPage(),
            'total'        => $events->total(),
        ], 'My registered events retrieved');
    }

    // My Donation History
    public function myDonations()
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $profile = $user->profile;
        if (!$profile) return $this->error('Donor profile not found', 404);

        $donations = DonationRequestRecipient::with([
            'donationRequest:id,blood_group,hospital_name,district,needed_at',
        ])
            ->where('donor_profile_id', $profile->id)
            ->where('response_status', 'donated')
            ->orderBy('responded_at', 'desc')
            ->paginate(20);

        return $this->success([
            'donations'    => $donations->items(),
            'current_page' => $donations->currentPage(),
            'last_page'    => $donations->lastPage(),
            'total'        => $donations->total(),
        ], 'Donation history retrieved');
    }

    // My Stats
    public function stats()
    {
        $user = $this->getUser();
        if (!$user) return $this->error('Unauthenticated', 401);

        $profile = $user->profile;
        if (!$profile) return $this->error('Donor profile not found', 404);

        $totalDonations = DonationRequestRecipient::where('donor_profile_id', $profile->id)
            ->where('response_status', 'donated')
            ->count();

        $totalRequests = DonationRequest::where('requester_user_id', $user->id)
            ->count();

        $fulfilledRequests = DonationRequest::where('requester_user_id', $user->id)
            ->where('status', 'fulfilled')
            ->count();

        $totalIncoming = DonationRequestRecipient::where('donor_profile_id', $profile->id)
            ->count();

        $totalAccepted = DonationRequestRecipient::where('donor_profile_id', $profile->id)
            ->whereIn('response_status', ['accepted', 'donated'])
            ->count();

        // Success rate = accepted+donated / total incoming
        $successRate = $totalIncoming > 0
            ? round($totalAccepted / $totalIncoming, 2)
            : 0;

        return $this->success([
            'trust_score'       => $profile->trust_score,
            'total_donations'   => $totalDonations,
            'total_requests'    => $totalRequests,
            'fulfilled_requests' => $fulfilledRequests,
            'success_rate'      => $successRate,
            'blood_group'       => $profile->blood_group,
            'is_available'      => $profile->is_available,
        ], 'Stats retrieved');
    }
}
