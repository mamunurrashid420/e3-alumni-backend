<?php

namespace App\Policies;

use App\Models\MembershipApplication;
use App\Models\User;

class MembershipApplicationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MembershipApplication $membershipApplication): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Anyone can create (public endpoint)
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MembershipApplication $membershipApplication): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, MembershipApplication $membershipApplication): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can reject the model.
     */
    public function reject(User $user, MembershipApplication $membershipApplication): bool
    {
        return $user->isSuperAdmin();
    }
}
