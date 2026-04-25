<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:user,organization',

            // user fields
            'name'     => 'required_if:role,user|string|max:100',

            // org fields
            'org_name'       => 'required_if:role,organization|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'documents'      => 'required_if:role,organization|array|min:1',
            'documents.*.document_type' => 'required_if:role,organization|in:trade_license,ngo_certificate,tax_certificate,other',
            'documents.*.document_url'  => 'required_if:role,organization|string|url',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'              => 'Email already registered.',
            'role.in'                   => 'Role must be user or organization.',
            'name.required_if'          => 'Full name is required.',
            'org_name.required_if'      => 'Organization name is required.',
            'documents.required_if'     => 'At least one document is required.',
            'documents.min'             => 'At least one document is required.',
            'documents.*.document_url.url' => 'Document URL must be a valid URL.',
        ];
    }
}
