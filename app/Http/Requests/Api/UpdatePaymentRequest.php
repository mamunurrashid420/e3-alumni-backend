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
            'payment_purpose' => ['sometimes', 'required', 'string', 'in:ASSOCIATE_MEMBERSHIP_FEES,GENERAL_MEMBERSHIP_FEES,LIFETIME_MEMBERSHIP_FEES,SPECIAL_YEARLY_CONTRIBUTION_EXECUTIVE,YEARLY_SUBSCRIPTION_ASSOCIATE_MEMBER,YEARLY_SUBSCRIPTION_GENERAL_MEMBER,YEARLY_SUBSCRIPTION_LIFETIME_MEMBER,DONATIONS,PATRON,OTHERS'],
            'payment_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'payment_proof_file' => ['sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
