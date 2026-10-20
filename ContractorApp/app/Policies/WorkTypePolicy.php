<?php

namespace App\Policies;

/**
 * Work types are part of project setup, so they use the projects permissions.
 */
class WorkTypePolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'projects';
    }
}
