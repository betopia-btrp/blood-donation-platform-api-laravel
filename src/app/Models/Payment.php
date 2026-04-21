<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'donation_request_id',
        'payer_user_id',
        'amount',
        'status',
        'confirmed_at'
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function donationRequest()
    {
        return $this->belongsTo(DonationRequest::class);
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_user_id');
    }
}