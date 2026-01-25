<?php

namespace App\Models;

use App\Enums\SelfDeclarationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SelfDeclaration extends Model
{
    /** @use HasFactory<\Database\Factories\SelfDeclarationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'signature_file',
        'secondary_member_type_id',
        'date',
        'status',
        'approved_by',
        'approved_at',
        'rejected_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SelfDeclarationStatus::class,
            'date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns this self-declaration.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the secondary member type requested.
     */
    public function secondaryMemberType(): BelongsTo
    {
        return $this->belongsTo(MemberType::class, 'secondary_member_type_id');
    }

    /**
     * Get the user who approved this self-declaration.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope a query to only include pending self-declarations.
     */
    public function scopePending($query)
    {
        return $query->where('status', SelfDeclarationStatus::Pending->value);
    }

    /**
     * Scope a query to only include approved self-declarations.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', SelfDeclarationStatus::Approved->value);
    }

    /**
     * Scope a query to only include rejected self-declarations.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', SelfDeclarationStatus::Rejected->value);
    }

    /**
     * Get the URL for the signature file.
     */
    public function getSignatureFileUrlAttribute(): ?string
    {
        if (! $this->signature_file) {
            return null;
        }

        // Use API route for secure file serving
        // Generate full URL with the API base URL
        $baseUrl = config('app.url');
        return "{$baseUrl}/api/self-declarations/{$this->id}/signature";
    }
}
