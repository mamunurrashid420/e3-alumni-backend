<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ScholarshipApplicationResource extends JsonResource
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
            'scholarship_id' => $this->scholarship_id,
            'scholarship' => $this->whenLoaded('scholarship', fn () => new ScholarshipResource($this->scholarship)),
            'applicant_name' => $this->applicant_name,
            'applicant_email' => $this->applicant_email,
            'applicant_phone' => $this->applicant_phone,
            'applicant_address' => $this->applicant_address,
            'class_or_grade' => $this->class_or_grade,
            'school_name' => $this->school_name,
            'parent_or_guardian_name' => $this->parent_or_guardian_name,
            'academic_proof_file' => $this->academic_proof_file ? Storage::disk('public')->url($this->academic_proof_file) : null,
            'other_document_file' => $this->other_document_file ? Storage::disk('public')->url($this->other_document_file) : null,
            'statement' => $this->statement,
            'user_id' => $this->user_id,
            'status' => $this->status?->value,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejected_reason' => $this->rejected_reason,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
