<?php

namespace App\Policies;

use App\Models\User;

class VisitPolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'visits';
    }

    public function create(User $user): bool
    {
        // Employee portal users can log their own visits.
        if ($user->isEmployeePortal()) {
            return true;
        }

        return parent::create($user);
    }
}
