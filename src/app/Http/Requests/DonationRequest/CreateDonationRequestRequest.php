<?php

namespace App\Http\Requests\DonationRequest;

use Illuminate\Foundation\Http\FormRequest;

class CreateDonationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'blood_group'    => 'required|in:A+,A-,B+,B-,O+,O-,AB+,AB-',
            'quantity'       => 'required|integer|min:1',
            'hospital_name'  => 'nullable|string|max:150',
            'division'       => 'nullable|string|max:100',
            'district'       => 'required|string|max:100',
            'area'           => 'nullable|string|max:150',
            'location'       => 'required|string',
            'note'           => 'nullable|string',
            'needed_at'      => 'required|date',
            'donor_ids'      => 'required|array|min:1',
            'donor_ids.*'    => 'integer|exists:user_profiles,id',
        ];
    }
}
