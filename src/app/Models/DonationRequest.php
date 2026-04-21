<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonationRequest extends Model
{
    protected $fillable = [
        'requester_user_id',
        'blood_group',
        'quantity',
        'hospital_name',
        'division',
        'district',
        'area',
        'location',
        'note',
        'status',
        'needed_at'
    ];

    protected $casts = [
        'needed_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function recipients()
    {
        return $this->hasMany(DonationRequestRecipient::class, 'request_id');
    }
}
