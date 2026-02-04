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
            'phone' => $this->phone,
            'role' => $this->role?->value,
            'primary_member_type' => $this->primary_member_type?->value,
            'secondary_member_type' => $this->whenLoaded('secondaryMemberType', function () {
                return [
                    'id' => $this->secondaryMemberType->id,
                    'name' => $this->secondaryMemberType->name,
                    'description' => $this->secondaryMemberType->description,
                ];
            }),
            'secondary_member_type_id' => $this->secondary_member_type_id,
            'latest_self_declaration' => $this->whenLoaded('selfDeclarations', function () use ($request) {
                $latest = $this->selfDeclarations->first();
                if (! $latest) {
                    return null;
                }

                return (new \App\Http\Resources\Api\SelfDeclarationResource($latest))->toArray($request);
            }),
            'member_id' => $this->member_id,
            'membership_expires_at' => $this->primary_member_type
                ? $this->resource->getMembershipExpiresAt()?->toIso8601String()
                : null,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'profile' => $this->whenLoaded('memberProfile', function () use ($request) {
                return $this->memberProfile
                    ? (new MemberProfileResource($this->memberProfile))->toArray($request)
                    : null;
            }),
        ];
    }
}
