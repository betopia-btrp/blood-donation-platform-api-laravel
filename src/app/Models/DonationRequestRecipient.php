<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasAuditFields;

class DonationRequestRecipient extends Model
{
    use HasFactory, HasAuditFields;
    protected $fillable = [
        'request_id',
        'donor_profile_id',
        'response_status',
        'sent_at',
        'responded_at',
        'note',
        'donor_confirmed',
        'requester_confirmed',
        'donor_confirmed_at',
        'requester_confirmed_at',
    ];

    protected $casts = [
        'sent_at'                => 'datetime',
        'responded_at'           => 'datetime',
        'donor_confirmed'        => 'boolean',
        'requester_confirmed'    => 'boolean',
        'donor_confirmed_at'     => 'datetime',
        'requester_confirmed_at' => 'datetime',
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
