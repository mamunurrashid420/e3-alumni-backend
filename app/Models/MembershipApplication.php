<?php

namespace App\Models;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MembershipApplicationStatus;
use App\Enums\StudentshipProofType;
use App\Enums\TShirtSize;
use App\PrimaryMemberType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MembershipApplication extends Model
{
    /** @use HasFactory<\Database\Factories\MembershipApplicationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'membership_type',
        'full_name',
        'name_bangla',
        'father_name',
        'mother_name',
        'gender',
        'jsc_year',
        'ssc_year',
        'studentship_proof_type',
        'studentship_proof_file',
        'highest_educational_degree',
        'present_address',
        'permanent_address',
        'email',
        'mobile_number',
        'profession',
        'designation',
        'institute_name',
        't_shirt_size',
        'blood_group',
        'entry_fee',
        'yearly_fee',
        'payment_years',
        'total_paid_amount',
        'receipt_file',
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
            'membership_type' => PrimaryMemberType::class,
            'gender' => Gender::class,
            'studentship_proof_type' => StudentshipProofType::class,
            't_shirt_size' => TShirtSize::class,
            'blood_group' => BloodGroup::class,
            'status' => MembershipApplicationStatus::class,
            'entry_fee' => 'decimal:2',
            'yearly_fee' => 'decimal:2',
            'total_paid_amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Get the user who approved this application.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope a query to only include pending applications.
     */
    public function scopePending($query)
    {
        return $query->where('status', MembershipApplicationStatus::Pending->value);
    }

    /**
     * Scope a query to only include approved applications.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', MembershipApplicationStatus::Approved->value);
    }

    /**
     * Scope a query to only include rejected applications.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', MembershipApplicationStatus::Rejected->value);
    }

    /**
     * Get the URL for the studentship proof file.
     */
    public function getStudentshipProofFileUrlAttribute(): ?string
    {
        if (! $this->studentship_proof_file) {
            return null;
        }

        return Storage::disk('public')->url($this->studentship_proof_file);
    }

    /**
     * Get the URL for the receipt file.
     */
    public function getReceiptFileUrlAttribute(): ?string
    {
        if (! $this->receipt_file) {
            return null;
        }

        return Storage::disk('public')->url($this->receipt_file);
    }
}
