<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationDocument extends Model
{
    protected $fillable = [
        'organization_id',
        'document_type',
        'document_url'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
