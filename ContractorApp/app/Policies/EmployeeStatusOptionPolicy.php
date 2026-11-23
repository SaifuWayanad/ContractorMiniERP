<?php

namespace App\Policies;

class EmployeeStatusOptionPolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'employees';
    }
}
