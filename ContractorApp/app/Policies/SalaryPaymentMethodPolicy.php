<?php

namespace App\Policies;

class SalaryPaymentMethodPolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'salary_payments';
    }
}
