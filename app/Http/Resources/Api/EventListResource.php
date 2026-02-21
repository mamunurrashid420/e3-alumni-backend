<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EventListResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'location' => $this->location,
            'event_at' => $this->event_at->toIso8601String(),
            'registration_opens_at' => $this->registration_opens_at->toIso8601String(),
            'registration_closes_at' => $this->registration_closes_at->toIso8601String(),
            'status' => $this->status?->value,
            'cover_photo' => $this->cover_photo ? Storage::disk('public')->url($this->cover_photo) : null,
            'fee' => $this->fee !== null ? (float) $this->fee : null,
            'registration_count' => $this->whenCounted('registrations'),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
