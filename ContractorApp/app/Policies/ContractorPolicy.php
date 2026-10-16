<?php

namespace App\Policies;

use App\Models\Contractor;
use App\Models\User;

class ContractorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->hasPermission('contractors.view');
    }

    public function view(User $user, Contractor $contractor): bool
    {
        return $user->isAdmin() && $user->hasPermission('contractors.view');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->hasPermission('contractors.create');
    }

    public function update(User $user, Contractor $contractor): bool
    {
        return $user->isAdmin() && $user->hasPermission('contractors.edit');
    }

    public function delete(User $user, Contractor $contractor): bool
    {
        return $user->isAdmin() && $user->hasPermission('contractors.delete');
    }

    /**
     * A contractor user viewing their own company profile.
     */
    public function viewProfile(User $user, Contractor $contractor): bool
    {
        return $user->contractor_id === $contractor->id && $user->hasPermission('profile.view');
    }

    /**
     * A contractor user editing their own company profile.
     */
    public function updateProfile(User $user, Contractor $contractor): bool
    {
        return $user->contractor_id === $contractor->id && $user->hasPermission('profile.edit');
    }
}
