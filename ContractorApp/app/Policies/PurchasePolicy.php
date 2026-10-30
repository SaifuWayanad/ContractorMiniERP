<?php

namespace App\Policies;

class PurchasePolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'purchases';
    }
}
