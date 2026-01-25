<?php

namespace App\Models;

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'member_id',
        'name',
        'address',
        'mobile_number',
        'payment_purpose',
        'payment_method',
        'payment_amount',
        'payment_proof_file',
        'status',
        'approved_by',
        'approved_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_purpose' => PaymentPurpose::class,
            'payment_amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Get the user who approved this payment.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope a query to only include pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::Pending->value);
    }

    /**
     * Scope a query to only include approved payments.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', PaymentStatus::Approved->value);
    }

    /**
     * Scope a query to only include rejected payments.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', PaymentStatus::Rejected->value);
    }

    /**
     * Get the URL for the payment proof file.
     */
    public function getPaymentProofFileUrlAttribute(): ?string
    {
        if (!$this->payment_proof_file) {
            return null;
        }

        // Use API route for secure file serving
        $baseUrl = config('app.url');
        return "{$baseUrl}/api/payments/{$this->id}/proof";
    }
}
