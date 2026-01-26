<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'member_id' => ['nullable', 'string', 'exists:users,member_id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['sometimes', 'required', 'string'],
            'mobile_number' => ['sometimes', 'required', 'string', 'max:20'],
            'payment_purpose' => ['sometimes', 'required', 'string', new \Illuminate\Validation\Rules\Enum(\App\Enums\PaymentPurpose::class)],
            'payment_method' => ['sometimes', 'required', 'string'],
            'payment_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'payment_proof_file' => ['sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
