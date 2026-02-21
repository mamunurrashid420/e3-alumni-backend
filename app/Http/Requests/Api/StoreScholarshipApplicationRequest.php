<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScholarshipApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    public function prepareForValidation(): void
    {
        if ($this->has('applicant_phone')) {
            $mobile = preg_replace('/[^0-9]/', '', $this->applicant_phone);
            if (strlen($mobile) === 13 && str_starts_with($mobile, '880')) {
                $mobile = substr($mobile, 2);
            }
            $this->merge(['applicant_phone' => $mobile]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scholarship_id' => ['required', 'integer', Rule::exists('scholarships', 'id')],
            'applicant_name' => ['required', 'string', 'max:255'],
            'applicant_email' => ['nullable', 'string', 'email', 'max:255'],
            'applicant_phone' => ['required', 'string', 'size:11'],
            'applicant_address' => ['nullable', 'string'],
            'class_or_grade' => ['nullable', 'string', 'max:255'],
            'school_name' => ['nullable', 'string', 'max:255'],
            'parent_or_guardian_name' => ['nullable', 'string', 'max:255'],
            'academic_proof_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'other_document_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'statement' => ['nullable', 'string'],
            'applicant_signature' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
