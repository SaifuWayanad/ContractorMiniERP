<?php

namespace App\Policies;

class SalaryPaymentPolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'salary_payments';
    }
}
