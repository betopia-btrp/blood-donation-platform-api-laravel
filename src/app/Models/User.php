<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active'
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function organization()
    {
        return $this->hasOne(Organization::class);
    }

    public function donationRequests()
    {
        return $this->hasMany(DonationRequest::class, 'requester_user_id');
    }

    public function reportsMade()
    {
        return $this->hasMany(Report::class, 'reporter_user_id');
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
}
