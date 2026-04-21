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
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:user,organization',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email already registered.',
            'password.confirmed' => 'Passwords do not match.',
            'role.in' => 'Role must be user or organization.',
        ];
    }
}
