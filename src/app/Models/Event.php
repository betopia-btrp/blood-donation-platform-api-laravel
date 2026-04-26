<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditFields;

class Event extends Model
{
    use HasFactory, HasAuditFields;

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'event_date',
        'location',
        'district',
        'division',
        'max_capacity',
        'banner_image',
        'status',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }
}
