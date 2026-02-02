<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Allow when updating own profile (PUT /user/profile) or when super admin (PUT /members/{user}/profile).
     */
    public function authorize(): bool
    {
        $routeUser = $this->route()?->parameter('user');

        return $routeUser === null || $this->user()?->isSuperAdmin() === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name_bangla' => ['nullable', 'string', 'max:255'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', Rule::in(['MALE', 'FEMALE', 'OTHER'])],
            'jsc_year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'ssc_year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'highest_educational_degree' => ['nullable', 'string', 'max:255'],
            'present_address' => ['nullable', 'string', 'max:1000'],
            'permanent_address' => ['nullable', 'string', 'max:1000'],
            'profession' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'institute_name' => ['nullable', 'string', 'max:255'],
            't_shirt_size' => ['nullable', 'string', 'max:50'],
            'blood_group' => ['nullable', 'string', 'max:20'],
        ];
    }
}
