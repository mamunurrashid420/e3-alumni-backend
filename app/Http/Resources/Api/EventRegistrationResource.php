<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EventRegistrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'ssc_jsc' => $this->ssc_jsc,
            'registered_at' => $this->registered_at->toIso8601String(),
            'notes' => $this->notes,
            'guest_count' => (int) $this->guest_count,
            'guest_details' => $this->guest_details,
            'participant_fee' => $this->participant_fee !== null ? (float) $this->participant_fee : null,
            'total_fees' => $this->total_fees !== null ? (float) $this->total_fees : null,
            'payment_document_url' => $this->payment_document_path
                ? Storage::disk('public')->url($this->payment_document_path)
                : null,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'member_id' => $user->member_id,
            ] : null,
        ];
    }
}
