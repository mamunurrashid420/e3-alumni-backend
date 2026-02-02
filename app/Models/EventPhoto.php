<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPhoto extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'path',
        'sort_order',
    ];

    /**
     * Get the event that owns the photo.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
