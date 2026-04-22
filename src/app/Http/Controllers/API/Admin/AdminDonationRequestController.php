<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonationRequest;
use App\Models\DonationRequestRecipient;
use App\Traits\ApiResponse;

class AdminDonationRequestController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $requests = DonationRequest::with(['requester:id,name,email'])
            ->withCount([
                'recipients',
                'recipients as accepted_count' => fn($q) => $q->where('response_status', 'accepted'),
                'recipients as donated_count'  => fn($q) => $q->where('response_status', 'donated'),
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->success([
            'requests'     => $requests->items(),
            'current_page' => $requests->currentPage(),
            'last_page'    => $requests->lastPage(),
            'total'        => $requests->total(),
        ], 'Donation requests retrieved');
    }

    public function show($id)
    {
        $request = DonationRequest::with(['requester:id,name,email'])
            ->withCount([
                'recipients',
                'recipients as accepted_count' => fn($q) => $q->where('response_status', 'accepted'),
                'recipients as donated_count'  => fn($q) => $q->where('response_status', 'donated'),
            ])
            ->find($id);

        if (!$request) return $this->error('Request not found', 404);

        $recipients = DonationRequestRecipient::with([
            'donorProfile:id,user_id,blood_group,trust_score',
            'donorProfile.user:id,name,email',
        ])
            ->where('request_id', $id)
            ->get()
            ->map(function ($item) {
                return [
                    'name'            => $item->donorProfile->user->name,
                    'email'           => $item->donorProfile->user->email,
                    'blood_group'     => $item->donorProfile->blood_group,
                    'trust_score'     => $item->donorProfile->trust_score,
                    'response_status' => $item->response_status,
                    'responded_at'    => $item->responded_at,
                ];
            });

        return $this->success([
            'request'    => $request,
            'recipients' => $recipients,
        ], 'Donation request details retrieved');
    }
}
