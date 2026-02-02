<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DownloadResource extends JsonResource
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
            'file_url' => $this->file_path ? Storage::disk('public')->url($this->file_path) : null,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
