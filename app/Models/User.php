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
}
