<?php

namespace App\Policies;

class DealerPolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'dealers';
    }
}
