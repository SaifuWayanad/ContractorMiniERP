<?php

namespace App\Policies;

class WorkPolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'works';
    }
}
