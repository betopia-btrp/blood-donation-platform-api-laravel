<?php

namespace App\Http\Requests\Donor;

use Illuminate\Foundation\Http\FormRequest;

class SearchDonorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'blood_group'  => 'nullable|in:A+,A-,B+,B-,O+,O-,AB+,AB-',
            'district'     => 'nullable|string|max:100',
            'division'     => 'nullable|string|max:100',
            'is_available' => 'nullable|boolean',
        ];
    }
}
