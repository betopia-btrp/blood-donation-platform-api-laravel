<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasAuditFields;

class Report extends Model
{
    use HasFactory, HasAuditFields;
    protected $fillable = [
        'reporter_user_id',
        'target_user_id',
        'target_donation_request_id',
        'target_event_id',
        'report_type',
        'reason',
        'status',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function targetDonationRequest()
    {
        return $this->belongsTo(DonationRequest::class, 'target_donation_request_id');
    }

    public function targetEvent()
    {
        return $this->belongsTo(Event::class, 'target_event_id');
    }
}
