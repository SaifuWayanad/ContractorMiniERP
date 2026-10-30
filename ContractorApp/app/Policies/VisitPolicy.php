<?php

namespace App\Policies;

class VisitPolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'visits';
    }
}
