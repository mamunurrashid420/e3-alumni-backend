<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\PrimaryMemberType;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'password',
        'role',
        'primary_member_type',
        'secondary_member_type_id',
        'member_id',
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
     * UNIQUENUMBER: 4-digit sequential number starting from 0001
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

        // Find the highest unique number for this membership type + year combination
        $pattern = "{$prefix}-{$year}-%";
        $existingIds = static::where('member_id', 'like', $pattern)
            ->whereNotNull('member_id')
            ->pluck('member_id')
            ->map(function ($memberId) {
                // Extract the number part (last 4 digits)
                $parts = explode('-', $memberId);

                return (int) end($parts);
            })
            ->filter()
            ->toArray();

        // Get the next sequential number
        $nextNumber = empty($existingIds) ? 1 : max($existingIds) + 1;

        // Format as 4-digit zero-padded string
        $uniqueNumber = str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$uniqueNumber}";
    }
}
