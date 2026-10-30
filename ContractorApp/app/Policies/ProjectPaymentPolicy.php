<?php

namespace App\Policies;

class ProjectPaymentPolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'payments';
    }
}
