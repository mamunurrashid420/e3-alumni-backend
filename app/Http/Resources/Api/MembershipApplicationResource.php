<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'membership_type' => $this->membership_type?->value,
            'full_name' => $this->full_name,
            'name_bangla' => $this->name_bangla,
            'father_name' => $this->father_name,
            'mother_name' => $this->mother_name,
            'gender' => $this->gender?->value,
            'jsc_year' => $this->jsc_year,
            'ssc_year' => $this->ssc_year,
            'studentship_proof_type' => $this->studentship_proof_type?->value,
            'studentship_proof_file' => $this->studentship_proof_file_url,
            'highest_educational_degree' => $this->highest_educational_degree,
            'present_address' => $this->present_address,
            'permanent_address' => $this->permanent_address,
            'email' => $this->email,
            'mobile_number' => $this->mobile_number,
            'profession' => $this->profession,
            'designation' => $this->designation,
            'institute_name' => $this->institute_name,
            't_shirt_size' => $this->t_shirt_size?->value,
            'blood_group' => $this->blood_group?->value,
            'entry_fee' => $this->entry_fee,
            'yearly_fee' => $this->yearly_fee,
            'payment_years' => $this->payment_years,
            'total_paid_amount' => $this->total_paid_amount,
            'receipt_file' => $this->receipt_file_url,
            'photo' => $this->photo_url,
            'signature' => $this->signature_url,
            'status' => $this->status?->value,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
