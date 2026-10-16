<?php

namespace App\Policies;

class ProjectPolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'projects';
    }
}
