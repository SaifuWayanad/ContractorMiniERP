<?php

namespace App\Policies;

class EmployeeDesignationPolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'employees';
    }
}
