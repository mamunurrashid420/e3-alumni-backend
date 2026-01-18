<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMembershipApplicationRequest extends FormRequest
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
            'membership_type' => ['sometimes', 'required', 'string', 'in:GENERAL,LIFETIME,ASSOCIATE'],
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'name_bangla' => ['sometimes', 'required', 'string', 'max:255'],
            'father_name' => ['sometimes', 'required', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['sometimes', 'required', 'string', 'in:MALE,FEMALE,OTHER'],
            'jsc_year' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y')],
            'ssc_year' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y')],
            'studentship_proof_type' => ['nullable', 'string', 'in:JSC,EIGHT,SSC,METRIC_CERTIFICATE,MARK_SHEET,OTHERS'],
            'studentship_proof_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'highest_educational_degree' => ['nullable', 'string', 'max:255'],
            'present_address' => ['sometimes', 'required', 'string'],
            'permanent_address' => ['sometimes', 'required', 'string'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'mobile_number' => ['sometimes', 'required', 'string', 'max:20'],
            'profession' => ['sometimes', 'required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'institute_name' => ['nullable', 'string', 'max:255'],
            't_shirt_size' => ['sometimes', 'required', 'string', 'in:XXXL,XXL,XL,L,M,S'],
            'blood_group' => ['sometimes', 'required', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'entry_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_years' => ['sometimes', 'required', 'integer', 'in:1,2,3'],
            'receipt_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
