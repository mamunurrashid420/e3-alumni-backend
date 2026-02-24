<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PublicMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $memberProfile = $this->relationLoaded('memberProfile')
            ? $this->getRelation('memberProfile')
            : null;

        $photoPath = $memberProfile?->photo;
        $photoUrl = $photoPath ? Storage::disk('public')->url($photoPath) : null;

        $secondaryType = $this->relationLoaded('secondaryMemberType')
            ? $this->getRelation('secondaryMemberType')
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'member_id' => $this->member_id,
            'primary_member_type' => $this->primary_member_type?->value,
            'secondary_member_type' => $secondaryType ? [
                'id' => $secondaryType->id,
                'name' => $secondaryType->name,
                'description' => $secondaryType->description,
            ] : null,
            'designation' => $memberProfile?->designation,
            'profession' => $memberProfile?->profession,
            'institute_name' => $memberProfile?->institute_name,
            'photo' => $photoUrl,
        ];
    }
}
