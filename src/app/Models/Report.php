<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'reporter_user_id',
        'target_id',
        'target_type',
        'report_type',
        'reason',
        'status'
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function target()
    {
        return match ($this->target_type) {
            'user'             => $this->belongsTo(User::class, 'target_id'),
            'donation_request' => $this->belongsTo(DonationRequest::class, 'target_id'),
            'event'            => $this->belongsTo(Event::class, 'target_id'),
            default            => null,
        };
    }
}
