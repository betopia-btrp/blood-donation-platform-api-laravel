<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrganizationDocument extends Model
{
    use HasFactory;
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
