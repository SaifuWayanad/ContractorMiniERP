<?php

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Limits queries to the signed-in user's contractor.
 *
 * Contractor users only ever see their own contractor's rows, so a record belonging to another
 * contractor behaves as if it does not exist (route model binding returns 404). Admin users and
 * code running without a signed-in user (console, queues, seeders) are not restricted.
 */
class ContractorScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $contractorId = self::currentContractorId();

        if ($contractorId !== null) {
            $builder->where($model->qualifyColumn('contractor_id'), $contractorId);
        }
    }

    public static function currentContractorId(): ?int
    {
        $user = Auth::user();

        return $user instanceof User ? $user->contractor_id : null;
    }
}
