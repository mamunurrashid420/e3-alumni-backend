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
        $application = $this->relationLoaded('approvedMembershipApplication')
            ? $this->getRelation('approvedMembershipApplication')
            : null;

        $photoUrl = null;
        if ($application?->photo) {
            $photoUrl = Storage::disk('public')->url($application->photo);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'member_id' => $this->member_id,
            'primary_member_type' => $this->primary_member_type?->value,
            'designation' => $application?->designation,
            'profession' => $application?->profession,
            'photo' => $photoUrl,
        ];
    }
}
