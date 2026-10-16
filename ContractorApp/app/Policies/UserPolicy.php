<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->hasPermission('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->hasPermission('users.view');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->hasPermission('users.create');
    }

    /**
     * Only a super admin may change another super admin's account.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin()
            && $user->hasPermission('users.edit')
            && (! $model->isSuperAdmin() || $user->isSuperAdmin());
    }

    /**
     * Users cannot delete themselves, and only a super admin may delete a super admin.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin()
            && $user->hasPermission('users.delete')
            && $user->isNot($model)
            && (! $model->isSuperAdmin() || $user->isSuperAdmin());
    }
}
