<?php

namespace App\Http\Controllers\API\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Traits\ApiResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class OrganizationProfileController extends Controller
{
    use ApiResponse;

    public function show()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return $this->error('Unauthenticated', 401);
        }

        $user->load('organization');

        return $this->success($user, 'Organization profile retrieved');
    }

    public function update(UpdateOrganizationRequest $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return $this->error('Unauthenticated', 401);
        }

        if ($request->has('name')) {
            $user->update(['name' => $request->name]);
        }

        $user->organization->update(
            $request->only([
                'org_name',
                'license_number',
                'contact_person'
            ])
        );

        $user->load('organization');

        return $this->success($user, 'Organization profile updated');
    }
}
