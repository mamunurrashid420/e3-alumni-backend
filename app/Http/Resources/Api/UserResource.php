<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role?->value,
            'primary_member_type' => $this->primary_member_type?->value,
            'secondary_member_type' => $this->whenLoaded('secondaryMemberType', function () {
                return [
                    'id' => $this->secondaryMemberType->id,
                    'name' => $this->secondaryMemberType->name,
                    'description' => $this->secondaryMemberType->description,
                ];
            }),
            'member_id' => $this->member_id,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
