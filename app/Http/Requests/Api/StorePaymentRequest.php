<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'payment_purpose' => ['required', 'string', 'in:ASSOCIATE_MEMBERSHIP_FEES,GENERAL_MEMBERSHIP_FEES,LIFETIME_MEMBERSHIP_FEES,SPECIAL_YEARLY_CONTRIBUTION_EXECUTIVE,DONATIONS,PATRON,OTHERS'],
            'payment_amount' => ['required', 'numeric', 'min:0'],
            'payment_proof_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
