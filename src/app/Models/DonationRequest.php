<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasAuditFields;

class DonationRequest extends Model
{
    use HasFactory, HasAuditFields;
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
