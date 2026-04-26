<?php

namespace App\Http\Controllers\API\DonationRequest;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonationRequest\CreateDonationRequestRequest;
use App\Models\DonationRequest;
use App\Models\DonationRequestRecipient;
use App\Models\Payment;
use App\Models\UserProfile;
use App\Models\Report;
use App\Traits\ApiResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;

class DonationRequestController extends Controller
{
    use ApiResponse;

    // Create Donation Request
    public function store(CreateDonationRequestRequest $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return $this->error('Unauthenticated', 401);
        }

        $donationRequest = DonationRequest::create([
            'requester_user_id' => $user->id,
            'blood_group'       => $request->blood_group,
            'quantity'          => $request->quantity,
            'hospital_name'     => $request->hospital_name,
            'division'          => $request->division,
            'district'          => $request->district,
            'area'              => $request->area,
            'location'          => $request->location,
            'note'              => $request->note,
            'needed_at'         => $request->needed_at,
            'status'            => 'open',
        ]);

        $recipients = [];
        foreach ($request->donor_ids as $profileId) {
            $recipients[] = DonationRequestRecipient::create([
                'request_id'      => $donationRequest->id,
                'donor_profile_id' => $profileId,
                'response_status' => 'pending',
                'sent_at'         => now(),
            ]);
        }

        return $this->success([
            'request'    => $donationRequest,
            'sent_to'    => count($recipients) . ' donors',
        ], 'Donation request created and sent to donors', 201);
    }

    // Get Request Details
    public function show($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return $this->error('Unauthenticated', 401);
        }

        $donationRequest = DonationRequest::with([
            'requester:id,name,email',
        ])->find($id);

        if (!$donationRequest) {
            return $this->error('Request not found', 404);
        }

        if ($donationRequest->requester_user_id !== $user->id && $user->role !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        return $this->success($donationRequest, 'Request details retrieved');
    }

    // Cancel Request
    public function destroy($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return $this->error('Unauthenticated', 401);
        }

        $donationRequest = DonationRequest::find($id);

        if (!$donationRequest) {
            return $this->error('Request not found', 404);
        }

        if ($donationRequest->requester_user_id !== $user->id) {
            return $this->error('Forbidden', 403);
        }

        if ($donationRequest->status !== 'open') {
            return $this->error('Only open requests can be cancelled', 400);
        }

        $donationRequest->update(['status' => 'cancelled']);

        return $this->success(null, 'Request cancelled');
    }

    // Get Accepted Donors List
    public function acceptances($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return $this->error('Unauthenticated', 401);
        }

        $donationRequest = DonationRequest::find($id);

        if (!$donationRequest) {
            return $this->error('Request not found', 404);
        }

        if ($donationRequest->requester_user_id !== $user->id) {
            return $this->error('Forbidden', 403);
        }

        $totalAccepted = DonationRequestRecipient::where('request_id', $id)
            ->where('response_status', 'accepted')
            ->count();

        return $this->success([
            'total_accepted' => $totalAccepted,
        ], 'Accepted donors count retrieved');
    }

    // Confirm Payment (dummy) 
    public function confirmPayment($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return $this->error('Unauthenticated', 401);
        }

        $donationRequest = DonationRequest::find($id);

        if (!$donationRequest) {
            return $this->error('Request not found', 404);
        }

        if ($donationRequest->requester_user_id !== $user->id) {
            return $this->error('Forbidden', 403);
        }

        $acceptedCount = DonationRequestRecipient::where('request_id', $id)
            ->where('response_status', 'accepted')
            ->count();

        if ($acceptedCount === 0) {
            return $this->error('No donors have accepted this request yet', 400);
        }

        $existing = Payment::where('donation_request_id', $id)->first();
        if ($existing) {
            return $this->error('Payment already confirmed for this request', 400);
        }

        Payment::create([
            'donation_request_id' => $donationRequest->id,
            'payer_user_id'       => $user->id,
            'amount'              => 0,
            'status'              => 'confirmed',
            'confirmed_at'        => now(),
        ]);

        return $this->success(null, 'Payment confirmed. You can now view donor contact info.');
    }

    // Donor Contact Info (after payment)
    public function donorsRevealed($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return $this->error('Unauthenticated', 401);
        }

        $donationRequest = DonationRequest::find($id);
        if (!$donationRequest) return $this->error('Request not found', 404);
        if ($donationRequest->requester_user_id !== $user->id) return $this->error('Forbidden', 403);

        $payment = Payment::where('donation_request_id', $id)
            ->where('status', 'confirmed')
            ->first();

        if (!$payment) return $this->error('Please confirm payment first', 403);

        $donors = DonationRequestRecipient::with([
            'donorProfile:id,user_id,blood_group,district,division,trust_score',
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

        return $this->success($donors, 'Donor contact info revealed');
    }

    public function complete($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return $this->error('Unauthenticated', 401);
        }

        $donationRequest = DonationRequest::find($id);

        if (!$donationRequest) {
            return $this->error('Request not found', 404);
        }

        if ($donationRequest->requester_user_id !== $user->id) {
            return $this->error('Forbidden', 403);
        }

        if ($donationRequest->status !== 'open') {
            return $this->error('Request is not open', 400);
        }

        $acceptedCount = DonationRequestRecipient::where('request_id', $id)
            ->where('response_status', 'accepted')
            ->count();

        if ($acceptedCount === 0) {
            return $this->error('Cannot complete request with no accepted donors', 400);
        }

        // Must have confirmed payment
        $payment = Payment::where('donation_request_id', $id)
            ->where('status', 'confirmed')
            ->first();

        if (!$payment) {
            return $this->error('Please confirm payment before completing request', 400);
        }

        $donationRequest->update(['status' => 'fulfilled']);

        return $this->success(null, 'Request marked as completed');
    }

    public function report(Request $request, $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return $this->error('Unauthenticated', 401);
        }

        $donationRequest = DonationRequest::find($id);

        if (!$donationRequest) {
            return $this->error('Request not found', 404);
        }

        Report::create([
            'reporter_user_id' => $user->id,
            'target_id'        => $id,
            'target_type'      => 'donation_request',
            'report_type'      => $request->input('report_type', 'other'),
            'reason'           => $request->input('reason'),
            'status'           => 'pending',
        ]);

        return $this->success(null, 'Report submitted');
    }
}
