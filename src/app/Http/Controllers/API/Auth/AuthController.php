<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Organization;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use App\Models\OrganizationDocument;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name'     => $request->role === 'user' ? $request->name : $request->org_name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        if ($user->role === 'user') {
            UserProfile::create(['user_id' => $user->id]);
        }

        if ($user->role === 'organization') {
            $org = Organization::create([
                'user_id'        => $user->id,
                'org_name'       => $request->org_name,
                'contact_person' => $request->contact_person,
            ]);

            foreach ($request->documents as $doc) {
                OrganizationDocument::create([
                    'organization_id' => $org->id,
                    'document_type'   => $doc['document_type'],
                    'document_url'    => $doc['document_url'],
                ]);
            }
        }

        $token = JWTAuth::fromUser($user);

        return $this->success([
            'user'  => $user,
            'token' => $token,
        ], 'Registration successful', 201);
    }

    public function login(LoginRequest $request)
    {
        $token = JWTAuth::attempt([
            'email'    => $request->email,
            'password' => $request->password,
        ]);

        if (!$token) {
            return $this->error('Invalid email or password', 401);
        }

        $user = JWTAuth::user();

        if (!$user->is_active) {
            return $this->error('Account has been deactivated', 403);
        }

        if ($user->role === 'organization') {
            $user->load('organization');

            // rejected = blocked
            if ($user->organization->verification_status === 'rejected') {
                return $this->error('Your organization account has been rejected', 403);
            }
        }

        $user = $this->loadProfile($user);

        return $this->success([
            'user'  => $user,
            'token' => $token,
        ], 'Login successful');
    }

    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return $this->error('User not found', 404);
            }

            $user = $this->loadProfile($user);

            return $this->success($user, 'Authenticated user');
        } catch (\Exception $e) {
            return $this->error('Invalid or expired token', 401);
        }
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return $this->success(null, 'Logged out successfully');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return $this->error('Unauthenticated', 401);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect', 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        JWTAuth::invalidate(JWTAuth::getToken());

        return $this->success(null, 'Password changed. Please login again.');
    }

    private function loadProfile(User $user): User
    {
        if ($user->role === 'user') {
            $user->load('profile');
        }

        if ($user->role === 'organization') {
            $user->load('organization');
        }

        return $user;
    }
}
