<?php

namespace App\Policies;

class EmployeeSalaryTypePolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'employees';
    }
}
