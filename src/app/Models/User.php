<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active'
    ];

    protected $hidden = ['password'];

    protected $with = ['role'];

    // JWT 
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Relationships ─
    public function role()
    {
        return $this->belongsTo(Role::class);
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

    public function reports()
    {
        return $this->hasMany(Report::class, 'reporter_user_id');
    }

    // Role Helpers 
    public function getRoleNameAttribute(): string
    {
        return $this->role?->name ?? '';
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isOrganization(): bool
    {
        return $this->hasRole('organization');
    }

    public function isUser(): bool
    {
        return $this->hasRole('user');
    }
}
