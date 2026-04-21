<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonationRequestRecipient extends Model
{
    protected $fillable = [
        'request_id',
        'donor_profile_id',
        'response_status',
        'sent_at',
        'responded_at',
        'note'
    ];

    protected $casts = [
        'sent_at'      => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function donationRequest()
    {
        return $this->belongsTo(DonationRequest::class, 'request_id');
    }

    public function donorProfile()
    {
        return $this->belongsTo(UserProfile::class, 'donor_profile_id');
    }
}
