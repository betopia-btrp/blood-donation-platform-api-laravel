<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Traits\ApiResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ProfileController extends Controller
{
    use ApiResponse;

    public function show()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return $this->error('Unauthenticated', 401);
        }

        $user->load('profile');

        return $this->success($user, 'Profile retrieved');
    }

    public function update(UpdateProfileRequest $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return $this->error('Unauthenticated', 401);
        }

        if ($request->has('name')) {
            $user->update(['name' => $request->name]);
        }

        $user->profile->update(
            $request->only([
                'blood_group',
                'division',
                'district',
                'area',
                'is_available',
                'last_donation_date',
                'avatar_url'
            ])
        );

        $user->load('profile');

        return $this->success($user, 'Profile updated');
    }
}
