<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared rules for records owned by a contractor.
 *
 * - Contractor users need the permission AND the record must belong to their own contractor.
 * - Admin users with the view permission can view any contractor's records (monitoring), but
 *   contractor data is created and changed only by the contractor's own users.
 */
abstract class ContractorOwnedPolicy
{
    /**
     * Permission group in config/permissions.php, e.g. "clients".
     */
    abstract protected function permissionGroup(): string;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission($this->permission('view'));
    }

    public function view(User $user, Model $record): bool
    {
        return $user->hasPermission($this->permission('view')) && $this->canAccess($user, $record);
    }

    public function create(User $user): bool
    {
        return ($user->isAdmin() || $user->isContractorUser()) && $user->hasPermission($this->permission('create'));
    }

    public function update(User $user, Model $record): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission($this->permission('edit'));
        }

        return $user->isContractorUser()
            && $user->hasPermission($this->permission('edit'))
            && $this->ownsRecord($user, $record);
    }

    public function delete(User $user, Model $record): bool
    {
        if ($user->isAdmin()) {
            return $user->hasPermission($this->permission('delete'));
        }

        return $user->isContractorUser()
            && $user->hasPermission($this->permission('delete'))
            && $this->ownsRecord($user, $record);
    }

    protected function canAccess(User $user, Model $record): bool
    {
        return $user->isAdmin() || $this->ownsRecord($user, $record);
    }

    protected function ownsRecord(User $user, Model $record): bool
    {
        return $user->contractor_id !== null && $user->contractor_id === $record->getAttribute('contractor_id');
    }

    private function permission(string $ability): string
    {
        return $this->permissionGroup().'.'.$ability;
    }
}
