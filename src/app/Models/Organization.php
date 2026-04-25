<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Organization extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'org_name',
        'license_number',
        'verification_status',
        'contact_person'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(OrganizationDocument::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
