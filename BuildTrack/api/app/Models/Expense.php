<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'project_id', 'expense_heading', 'expense_description', 'expense_date', 'amount',
    'payment_method', 'reference_number', 'notes', 'is_archived',
])]
class Expense extends Model
{
    protected function casts(): array
    {
        return [
            'expense_date' => 'datetime',
            'amount' => 'decimal:2',
            'is_archived' => 'boolean',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
