<?php

namespace App\Models;

use App\Models\Concerns\BelongsToContractor;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['project_id', 'payment_date', 'amount_received', 'payment_method', 'receipt_reference', 'notes'])]
class ProjectPayment extends Model
{
    use BelongsToContractor, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'payment_date'    => 'date',
            'amount_received' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function activityLabel(): string
    {
        return 'Payment of ₹'.number_format((float) $this->amount_received, 2);
    }
}
