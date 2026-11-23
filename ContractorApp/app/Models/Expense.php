<?php

namespace App\Models;

use App\Models\Concerns\BelongsToContractor;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['project_id', 'work_id', 'heading', 'category', 'is_work_related', 'expense_date', 'amount', 'payment_method', 'reference_number', 'notes'])]
class Expense extends Model
{
    use BelongsToContractor, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'expense_date'    => 'date',
            'amount'          => 'decimal:2',
            'is_work_related' => 'boolean',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function activityLabel(): string
    {
        return $this->heading;
    }
}
