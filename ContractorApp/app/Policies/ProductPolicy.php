<?php

namespace App\Policies;

class ProductPolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'products';
    }
}
