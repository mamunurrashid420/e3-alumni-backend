<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize phone to 11 digits (BD format) before validation.
     */
    public function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $phone = $this->phone;
            $phone = preg_replace('/[^0-9]/', '', (string) $phone);
            if (strlen($phone) === 13 && str_starts_with($phone, '880')) {
                $phone = substr($phone, 2);
            }
            $this->merge(['phone' => $phone]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $member = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($member->id),
            ],
            'phone' => [
                'required',
                'string',
                'size:11',
                Rule::unique('users', 'phone')->ignore($member->id),
            ],
        ];
    }
}
