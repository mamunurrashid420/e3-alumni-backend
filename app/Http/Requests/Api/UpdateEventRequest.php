<?php

namespace App\Http\Requests\Api;

use App\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
            'event_at' => ['sometimes', 'required', 'date'],
            'registration_opens_at' => ['sometimes', 'required', 'date'],
            'registration_closes_at' => ['sometimes', 'required', 'date', 'after:registration_opens_at'],
            'status' => ['sometimes', 'required', 'string', Rule::in([EventStatus::Draft->value, EventStatus::Open->value, EventStatus::Closed->value])],
            'cover_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }
}
