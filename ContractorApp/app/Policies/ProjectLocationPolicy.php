<?php

namespace App\Policies;

class ProjectLocationPolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'locations';
    }
}
