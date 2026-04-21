<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

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
}
