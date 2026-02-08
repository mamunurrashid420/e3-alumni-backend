<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'events';

    /**
     * Get the attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'short_description',
        'location',
        'event_at',
        'registration_opens_at',
        'registration_closes_at',
        'status',
        'cover_photo',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_at' => 'datetime',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'status' => EventStatus::class,
        ];
    }

    /**
     * Get the photos for the event (gallery when closed).
     */
    public function photos(): HasMany
    {
        return $this->hasMany(EventPhoto::class)->orderBy('sort_order');
    }

    /**
     * Get the registrations for the event.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * Get the users (members) registered for the event.
     */
    public function registeredUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_registrations')
            ->withPivot('registered_at')
            ->withTimestamps();
    }

    /**
     * Scope to only open events.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', EventStatus::Open);
    }

    /**
     * Scope to only closed events.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', EventStatus::Closed);
    }

    /**
     * Scope to only upcoming events (registration not yet closed).
     * Events appear as upcoming while registration is still open.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('registration_closes_at', '>=', now());
    }

    /**
     * Check if registration is currently open (within the registration window).
     */
    public function isRegistrationOpen(): bool
    {
        $now = now();

        return $this->registration_opens_at <= $now && $this->registration_closes_at >= $now;
    }
}
