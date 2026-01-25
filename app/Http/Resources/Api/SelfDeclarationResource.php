<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SelfDeclarationResource extends JsonResource
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
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                    'member_id' => $this->user->member_id,
                ];
            }),
            'name' => $this->name,
            'signature_file' => $this->signature_file_url,
            'secondary_member_type' => $this->whenLoaded('secondaryMemberType', function () {
                return [
                    'id' => $this->secondaryMemberType->id,
                    'name' => $this->secondaryMemberType->name,
                    'description' => $this->secondaryMemberType->description,
                ];
            }),
            'date' => $this->date->format('Y-m-d'),
            'status' => $this->status?->value,
            'approved_by' => $this->whenLoaded('approvedBy', function () {
                return [
                    'id' => $this->approvedBy->id,
                    'name' => $this->approvedBy->name,
                ];
            }),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejected_reason' => $this->rejected_reason,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
