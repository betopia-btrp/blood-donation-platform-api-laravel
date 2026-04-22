<?php

namespace App\Http\Controllers\API\Donor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Donor\SearchDonorRequest;
use App\Models\UserProfile;
use App\Models\User;
use App\Traits\ApiResponse;

class DonorController extends Controller
{
    use ApiResponse;

    // Search Or Filter Donors
    public function index(SearchDonorRequest $request)
    {
        $query = UserProfile::query()
            ->whereHas('user', function ($q) {
                $q->where('is_active', true)
                    ->where('role', 'user');
            })
            ->with([
                'user:id,name,role',
            ]);

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

        return $this->success([
            'donors'       => $donors->items(),
            'current_page' => $donors->currentPage(),
            'last_page'    => $donors->lastPage(),
            'per_page'     => $donors->perPage(),
            'total'        => $donors->total(),
        ], 'Donors retrieved');
    }

    // Donor Public Profile
    public function show($id)
    {
        $profile = UserProfile::with(['user:id,name,role'])
            ->whereHas('user', function ($q) {
                $q->where('is_active', true)
                    ->where('role', 'user');
            })
            ->find($id);

        if (!$profile) {
            return $this->error('Donor not found', 404);
        }

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
        ];

        return $this->success($data, 'Donor profile retrieved');
    }
}
