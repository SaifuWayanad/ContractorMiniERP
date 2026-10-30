<?php

namespace App\Policies;

class ExpensePolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'expenses';
    }
}
