<?php

namespace App\Models;

use App\Models\Scopes\ContractorScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Activity rows are scoped to the signed-in contractor like other contractor data, but may have no
 * contractor (admin actions), so they do not use the BelongsToContractor trait's create rules.
 */
#[Fillable(['action', 'entity_type', 'entity_id', 'description', 'ip_address'])]
#[ScopedBy([ContractorScope::class])]
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Contractor, $this>
     */
    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class)->withTrashed();
    }

    public function icon(): string
    {
        return match ($this->action) {
            'created' => 'bi-plus-circle',
            'updated' => 'bi-pencil',
            'deleted' => 'bi-trash',
            'completed' => 'bi-check-circle',
            'status_changed' => 'bi-flag',
            'login' => 'bi-box-arrow-in-right',
            'logout' => 'bi-box-arrow-right',
            default => 'bi-dot',
        };
    }
}
