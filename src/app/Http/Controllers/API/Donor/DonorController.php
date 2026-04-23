<?php

namespace App\Http\Controllers\API\Donor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Donor\SearchDonorRequest;
use App\Models\DonationRequestRecipient;
use App\Models\UserProfile;
use App\Traits\ApiResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class DonorController extends Controller
{
    use ApiResponse;

    private function getOptionalUser()
    {
        try {
            $token = JWTAuth::getToken();
            if (!$token) return null;
            return JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return null;
        }
    }

    // For Guest or Authenticate User
    private function injectDonorContext(object $profile, $user): array
    {
        $data = $profile->toArray();

        if ($user) {
            $requested = DonationRequestRecipient::whereHas('donationRequest', function ($q) use ($user) {
                $q->where('requester_user_id', $user->id)
                    ->where('status', 'open');
            })
                ->where('donor_profile_id', $profile->id)
                ->first();

            $data['is_requested']      = (bool) $requested;
            $data['request_status']    = $requested?->response_status;
        } else {
            $data['is_requested']      = null;
            $data['request_status']    = null;
        }

        return $data;
    }

    public function index(SearchDonorRequest $request)
    {
        $user  = $this->getOptionalUser();
        $query = UserProfile::query()
            ->whereHas('user', function ($q) {
                $q->where('is_active', true)->where('role', 'user');
            })
            ->with(['user:id,name,role']);

        if ($request->filled('blood_group')) {
            $query->where('blood_group', $request->blood_group);
        }

        if ($request->filled('district')) {
            $query->where('district', 'ilike', '%' . $request->district . '%');
        }

        if ($request->filled('division')) {
            $query->where('division', 'ilike', '%' . $request->division . '%');
        }

        if ($request->filled('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }

        $donors = $query->orderBy('trust_score', 'desc')->paginate(20);

        $data = collect($donors->items())->map(function ($profile) use ($user) {
            return $this->injectDonorContext($profile, $user);
        });

        return $this->success([
            'donors'       => $data,
            'current_page' => $donors->currentPage(),
            'last_page'    => $donors->lastPage(),
            'total'        => $donors->total(),
        ], 'Donors retrieved');
    }

    public function show($id)
    {
        $user    = $this->getOptionalUser();
        $profile = UserProfile::with(['user:id,name,role'])
            ->whereHas('user', function ($q) {
                $q->where('is_active', true)->where('role', 'user');
            })
            ->find($id);

        if (!$profile) return $this->error('Donor not found', 404);

        $data = [
            'id'                 => $profile->id,
            'name'               => $profile->user->name,
            'blood_group'        => $profile->blood_group,
            'division'           => $profile->division,
            'district'           => $profile->district,
            'area'               => $profile->area,
            'is_available'       => $profile->is_available,
            'trust_score'        => $profile->trust_score,
            'last_donation_date' => $profile->last_donation_date,
            'is_requested'       => null,
            'request_status'     => null,
        ];

        if ($user) {
            $requested = DonationRequestRecipient::whereHas('donationRequest', function ($q) use ($user) {
                $q->where('requester_user_id', $user->id)
                    ->where('status', 'open');
            })
                ->where('donor_profile_id', $profile->id)
                ->first();

            $data['is_requested']  = (bool) $requested;
            $data['request_status'] = $requested?->response_status;
        }

        return $this->success($data, 'Donor profile retrieved');
    }
}
