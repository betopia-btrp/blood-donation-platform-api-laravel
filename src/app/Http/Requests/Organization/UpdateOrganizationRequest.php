<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'org_name'       => 'nullable|string|max:150',
            'license_number' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:100',
            'name'           => 'nullable|string|max:100',
        ];
    }
}
