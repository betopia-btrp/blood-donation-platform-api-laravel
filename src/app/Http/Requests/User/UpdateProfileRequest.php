<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'blood_group'        => 'nullable|in:A+,A-,B+,B-,O+,O-,AB+,AB-',
            'division'           => 'nullable|string|max:100',
            'district'           => 'nullable|string|max:100',
            'area'               => 'nullable|string|max:150',
            'is_available'       => 'nullable|boolean',
            'last_donation_date' => 'nullable|date',
            'avatar_url'         => 'nullable|string',
            'name'               => 'nullable|string|max:100',
            'phone'              => 'nullable|string|max:20',
        ];
    }
}
