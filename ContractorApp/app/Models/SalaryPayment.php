<?php

namespace App\Models;

use App\Enums\SalaryPaymentStatus;
use App\Models\Concerns\BelongsToContractor;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['employee_id', 'period_month', 'period_year', 'amount', 'payment_date', 'payment_method', 'status', 'notes'])]
class SalaryPayment extends Model
{
    use BelongsToContractor, LogsActivity;

    protected function casts(): array
    {
        return [
            'status' => SalaryPaymentStatus::class,
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function activityLabel(): string
    {
        return "Salary #{$this->id}";
    }
}
