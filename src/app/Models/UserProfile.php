<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasAuditFields;

class UserProfile extends Model
{
    use HasFactory, HasAuditFields;
    protected $fillable = [
        'user_id',
        'blood_group',
        'division',
        'district',
        'area',
        'is_available',
        'last_donation_date',
        'avatar_url'
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'last_donation_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function donationRequestRecipients()
    {
        return $this->hasMany(DonationRequestRecipient::class, 'donor_profile_id');
    }

    public function eventRegistrations()
    {
        return $this->hasMany(EventRegistration::class, 'profile_id');
    }
}
