<?php

namespace App\Policies;

class ClientPolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'clients';
    }
}
