<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'user_id',
        'name',
        'phone',
        'address',
        'ssc_jsc',
        'registered_at',
        'notes',
        'guest_count',
        'guest_details',
        'participant_fee',
        'total_fees',
        'payment_document_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'participant_fee' => 'decimal:2',
            'total_fees' => 'decimal:2',
        ];
    }

    /**
     * Get the event for the registration.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user for the registration.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
