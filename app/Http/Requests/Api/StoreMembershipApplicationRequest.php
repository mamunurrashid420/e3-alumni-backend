<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipApplicationRequest extends FormRequest
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
            'membership_type' => ['required', 'string', 'in:GENERAL,LIFETIME,ASSOCIATE'],
            'full_name' => ['required', 'string', 'max:255'],
            'name_bangla' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'string', 'in:MALE,FEMALE,OTHER'],
            'jsc_year' => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'ssc_year' => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'studentship_proof_type' => ['nullable', 'string', 'in:JSC,EIGHT,SSC,METRIC_CERTIFICATE,MARK_SHEET,OTHERS'],
            'studentship_proof_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'highest_educational_degree' => ['nullable', 'string', 'max:255'],
            'present_address' => ['required', 'string'],
            'permanent_address' => ['required', 'string'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'profession' => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'institute_name' => ['nullable', 'string', 'max:255'],
            't_shirt_size' => ['required', 'string', 'in:XXXL,XXL,XL,L,M,S'],
            'blood_group' => ['required', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'entry_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_years' => ['required', 'string'],
            'payment_method' => ['required', 'string', 'max:255'],
            'receipt_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
