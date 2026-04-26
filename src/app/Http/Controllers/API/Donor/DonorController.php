<?php

namespace App\Http\Controllers\API\Donor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Donor\SearchDonorRequest;
use App\Models\DonationRequestRecipient;
use App\Models\Report;
use App\Models\User;
use App\Models\UserProfile;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class DonorController extends Controller
{
    use ApiResponse;

    private function getOptionalUser()
    {
        try {
            $token = JWTAuth::getToken();
            if (!$token)
                return null;
            return JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function index(SearchDonorRequest $request)
    {
        $user = $this->getOptionalUser();
        $query = UserProfile::query()
            ->whereHas('user', function ($q) use ($user) {
                $q->where('is_active', true)
                    ->whereHas('role', fn($r) => $r->where('name', 'user'));
                if ($user) {
                    $q->where('id', '!=', $user->id);
                }
            })
            ->where('is_available', true)
            ->with(['user:id,name,role_id']);

        if ($request->filled('blood_group')) {
            $query->where('blood_group', $request->blood_group);
        }
        if ($request->filled('district')) {
            $query->where('district', 'ilike', '%' . $request->district . '%');
        }
        if ($request->filled('division')) {
            $query->where('division', 'ilike', '%' . $request->division . '%');
        }

        $donors = $query->orderBy('trust_score', 'desc')->paginate(20);

        $requestedDonorIds = [];
        $requestedStatuses = [];

        if ($user) {
            $recipients = DonationRequestRecipient::whereHas('donationRequest', function ($q) use ($user) {
                $q->where('requester_user_id', $user->id)
                    ->where('status', 'open');
            })
                ->whereIn('donor_profile_id', collect($donors->items())->pluck('id'))
                ->get(['donor_profile_id', 'response_status']);

            foreach ($recipients as $r) {
                $requestedDonorIds[] = $r->donor_profile_id;
                $requestedStatuses[$r->donor_profile_id] = $r->response_status;
            }
        }

        $data = collect($donors->items())->map(function ($profile) use ($user, $requestedDonorIds, $requestedStatuses) {
            $arr = $profile->toArray();

            if ($user) {
                $isRequested = in_array($profile->id, $requestedDonorIds);
                $arr['is_requested'] = $isRequested;
                $arr['request_status'] = $isRequested ? $requestedStatuses[$profile->id] : null;
            } else {
                $arr['is_requested'] = null;
                $arr['request_status'] = null;
            }

            return $arr;
        });

        return $this->success([
            'donors' => $data,
            'current_page' => $donors->currentPage(),
            'last_page' => $donors->lastPage(),
            'total' => $donors->total(),
        ], 'Donors retrieved');
    }

    public function show($id)
    {
        $user = $this->getOptionalUser();
        $profile = UserProfile::with(['user:id,name,role_id'])
            ->whereHas('user', function ($q) {
                $q->where('is_active', true)
                    ->whereHas('role', fn($r) => $r->where('name', 'user'));
            })
            ->find($id);

        if (!$profile)
            return $this->error('Donor not found', 404);

        $data = [
            'id' => $profile->id,
            'name' => $profile->user->name,
            'blood_group' => $profile->blood_group,
            'division' => $profile->division,
            'district' => $profile->district,
            'area' => $profile->area,
            'is_available' => $profile->is_available,
            'trust_score' => $profile->trust_score,
            'last_donation_date' => $profile->last_donation_date,
            'is_requested' => null,
            'request_status' => null,
        ];

        if ($user) {
            $requested = DonationRequestRecipient::whereHas('donationRequest', function ($q) use ($user) {
                $q->where('requester_user_id', $user->id)
                    ->where('status', 'open');
            })
                ->where('donor_profile_id', $profile->id)
                ->first();

            $data['is_requested'] = (bool) $requested;
            $data['request_status'] = $requested?->response_status;
        }

        return $this->success($data, 'Donor profile retrieved');
    }

    public function report(Request $request, $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return $this->error('Unauthenticated', 401);
        }

        if ((int) $id === $user->id) {
            return $this->error('You cannot report yourself', 400);
        }

        $target = User::where('id', $id)->where('is_active', true)->first();

        if (!$target) {
            return $this->error('User not found', 404);
        }

        $request->validate([
            'report_type' => 'nullable|in:spam,fake,abusive,other',
            'reason'      => 'nullable|string',
        ]);

        Report::create([
            'reporter_user_id' => $user->id,
            'target_user_id'   => $id,
            'report_type'      => $request->input('report_type', 'other'),
            'reason'           => $request->input('reason'),
            'status'           => 'pending',
        ]);

        return $this->success(null, 'Report submitted');
    }
}
