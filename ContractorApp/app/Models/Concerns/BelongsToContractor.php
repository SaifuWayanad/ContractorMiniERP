<?php

namespace App\Models\Concerns;

use App\Models\Contractor;
use App\Models\Scopes\ContractorScope;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * For models owned by a contractor (they have a `contractor_id` column).
 *
 * - Queries are scoped to the signed-in contractor (see ContractorScope).
 * - On create, `contractor_id` always comes from the signed-in contractor user, never from input.
 * - A record can never be moved to another contractor.
 *
 * `contractor_id` must not be mass assignable on these models.
 */
trait BelongsToContractor
{
    public static function bootBelongsToContractor(): void
    {
        static::addGlobalScope(new ContractorScope);

        static::creating(function (Model $model): void {
            $contractorId = ContractorScope::currentContractorId();

            if ($contractorId !== null) {
                $model->setAttribute('contractor_id', $contractorId);
            }

            if ($model->getAttribute('contractor_id') === null) {
                throw new LogicException(class_basename($model).' must belong to a contractor.');
            }
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty('contractor_id')) {
                throw new LogicException('A record cannot be moved to another contractor.');
            }
        });
    }

    /**
     * @return BelongsTo<Contractor, $this>
     */
    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    /**
     * Explicitly limit a query to one contractor (used by admin screens filtering by contractor).
     */
    #[Scope]
    protected function forContractor(Builder $query, int $contractorId): Builder
    {
        return $query->where($this->qualifyColumn('contractor_id'), $contractorId);
    }
}
