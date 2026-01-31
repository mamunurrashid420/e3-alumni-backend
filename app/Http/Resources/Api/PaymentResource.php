<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PaymentResource extends JsonResource
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
            'member_id' => $this->member_id,
            'name' => $this->name,
            'address' => $this->address,
            'mobile_number' => $this->mobile_number,
            'payment_purpose' => $this->payment_purpose?->value,
            'payment_amount' => $this->payment_amount,
            'payment_proof_file' => $this->payment_proof_file ? Storage::disk('public')->url($this->payment_proof_file) : null,
            'receipt_file' => $this->receipt_file ? Storage::disk('public')->url($this->receipt_file) : null,
            'status' => $this->status?->value,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
