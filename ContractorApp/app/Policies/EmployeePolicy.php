<?php

namespace App\Policies;

class EmployeePolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'employees';
    }
}
