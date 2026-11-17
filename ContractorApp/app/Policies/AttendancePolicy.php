<?php

namespace App\Policies;

class AttendancePolicy extends ContractorOwnedPolicy
{
    protected function permissionGroup(): string
    {
        return 'attendance';
    }
}
