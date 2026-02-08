<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\PrimaryMemberType;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'primary_member_type',
        'secondary_member_type_id',
        'member_id',
        'membership_expires_at',
        'membership_renewed_at',
        'disabled_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'membership_expires_at' => 'datetime',
            'membership_renewed_at' => 'datetime',
            'disabled_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'primary_member_type' => PrimaryMemberType::class,
        ];
    }

    /**
     * Get the secondary member type that belongs to the user.
     */
    public function secondaryMemberType(): BelongsTo
    {
        return $this->belongsTo(MemberType::class, 'secondary_member_type_id');
    }

    /**
     * Get the member profile (1:1 for members; created from approved application).
     */
    public function memberProfile(): HasOne
    {
        return $this->hasOne(MemberProfile::class);
    }

    /**
     * Get the self-declarations for this user.
     */
    public function selfDeclarations(): HasMany
    {
        return $this->hasMany(SelfDeclaration::class);
    }

    /**
     * Get the events the user has registered for.
     */
    public function eventRegistrations(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_registrations')
            ->withPivot('registered_at')
            ->withTimestamps();
    }

    /**
     * Get the membership application associated with this user (by email or phone).
     */
    public function membershipApplication()
    {
        $query = \App\Models\MembershipApplication::query()
            ->where('status', \App\Enums\MembershipApplicationStatus::Approved);

        if ($this->email) {
            $query->where('email', $this->email);
        } elseif ($this->phone) {
            $query->where('mobile_number', $this->phone);
        } else {
            return null;
        }

        return $query->latest()->first();
    }

    /**
     * Check if the user has an email address.
     */
    public function hasEmail(): bool
    {
        return ! empty($this->email);
    }

    /**
     * Check if the user has a phone number.
     */
    public function hasPhone(): bool
    {
        return ! empty($this->phone);
    }

    /**
     * Get the login identifier (email or phone).
     */
    public function getLoginIdentifier(): ?string
    {
        return $this->email ?? $this->phone;
    }

    /**
     * Check if the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    /**
     * Check if the user is a member.
     */
    public function isMember(): bool
    {
        return $this->role === UserRole::Member;
    }

    /**
     * Check if the user account is disabled.
     */
    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
    }

    /**
     * Get the date when this member's membership expires (exact time: approved_at + payment_years).
     * Returns null for LIFETIME or when no approved application exists.
     * Uses stored membership_expires_at when set; otherwise computed from approved application.
     */
    public function getMembershipExpiresAt(): ?\Carbon\Carbon
    {
        if (! $this->primary_member_type || $this->primary_member_type === PrimaryMemberType::Lifetime) {
            return null;
        }

        if ($this->membership_expires_at !== null) {
            return $this->membership_expires_at;
        }

        $application = $this->membershipApplication();
        if (! $application || ! $application->approved_at) {
            return null;
        }

        $years = (int) $application->payment_years;
        if ($years < 1) {
            return null;
        }

        return $application->approved_at->copy()->addYears($years);
    }

    /**
     * Compute membership expiry date from approval date and number of years paid.
     * Expiry is the exact date/time: approved_at + payment_years (e.g. approved 2026-01-26 17:56:09, 1 year → 2027-01-26 17:56:09).
     */
    public static function computeMembershipExpiresAt(?\Carbon\Carbon $approvedAt, int $paymentYears): ?\Carbon\Carbon
    {
        if (! $approvedAt || $paymentYears < 1) {
            return null;
        }

        return $approvedAt->copy()->addYears($paymentYears);
    }

    /**
     * Extend membership by the given number of years from the current expiry (or from now if expired/none).
     */
    public function extendMembershipExpiryByYears(int $years): \Carbon\Carbon
    {
        $current = $this->getMembershipExpiresAt();
        $base = ($current && $current->isFuture()) ? $current : now();

        return $base->copy()->addYears($years);
    }

    /**
     * Check if the user has a specific primary member type.
     */
    public function hasPrimaryMemberType(PrimaryMemberType $type): bool
    {
        return $this->primary_member_type === $type;
    }

    /**
     * Check if the user has a secondary member type.
     */
    public function hasSecondaryMemberType(): bool
    {
        return $this->secondary_member_type_id !== null;
    }

    /**
     * Generate a unique member ID.
     *
     * Format: MEMBERSHIPTYPE-YEAR-UNIQUENUMBER
     * MEMBERSHIPTYPE: G (General), LT (Lifetime), A (Associate)
     * YEAR: SSC year (or JSC if SSC is null)
     * UNIQUENUMBER: 4-digit sequential number shared across all member types
     *
     * The last 4 digits increment sequentially across all member types and years.
     * For example: G-2000-0001, LT-2020-0002, A-2015-0003
     */
    public static function generateMemberId(PrimaryMemberType $type, ?int $sscYear, ?int $jscYear): string
    {
        // Map membership type to prefix
        $prefix = match ($type) {
            PrimaryMemberType::General => 'G',
            PrimaryMemberType::Lifetime => 'LT',
            PrimaryMemberType::Associate => 'A',
        };

        // Use SSC year if available, otherwise JSC year
        $year = $sscYear ?? $jscYear;

        if ($year === null) {
            throw new \InvalidArgumentException('Either SSC year or JSC year must be provided');
        }

        // Find the highest unique number across ALL member IDs (regardless of type or year)
        $existingIds = static::whereNotNull('member_id')
            ->pluck('member_id')
            ->map(function ($memberId) {
                // Extract the number part (last 4 digits after the last hyphen)
                $parts = explode('-', $memberId);
                $lastPart = end($parts);

                // Validate that the last part is numeric
                if (is_numeric($lastPart)) {
                    return (int) $lastPart;
                }

                return null;
            })
            ->filter()
            ->toArray();

        // Get the next sequential number (shared across all types)
        $nextNumber = empty($existingIds) ? 1 : max($existingIds) + 1;

        // Format as 4-digit zero-padded string
        $uniqueNumber = str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$uniqueNumber}";
    }
}
