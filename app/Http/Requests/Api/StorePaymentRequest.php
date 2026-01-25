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
        $user = $this->user();
        $isAuthenticated = $user !== null;

        return [
            'member_id' => ['nullable', 'string', 'exists:users,member_id'],
            'name' => $isAuthenticated
                ? ['nullable', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'address' => $isAuthenticated
                ? ['nullable', 'string']
                : ['required', 'string'],
            'mobile_number' => $isAuthenticated
                ? ['nullable', 'string', 'max:20']
                : ['required', 'string', 'max:20'],
            'payment_purpose' => ['required', 'string', 'in:ASSOCIATE_MEMBERSHIP_FEES,GENERAL_MEMBERSHIP_FEES,LIFETIME_MEMBERSHIP_FEES,SPECIAL_YEARLY_CONTRIBUTION_EXECUTIVE,YEARLY_SUBSCRIPTION_ASSOCIATE_MEMBER,YEARLY_SUBSCRIPTION_GENERAL_MEMBER,YEARLY_SUBSCRIPTION_LIFETIME_MEMBER,DONATIONS,PATRON,OTHERS'],
            'payment_amount' => ['required', 'numeric', 'min:0'],
            'payment_proof_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
