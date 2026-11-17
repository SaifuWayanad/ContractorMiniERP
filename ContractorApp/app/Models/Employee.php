<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\SalaryType;
use App\Models\Concerns\BelongsToContractor;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'employee_code', 'phone', 'email', 'designation', 'joining_date', 'salary_type', 'salary_amount', 'status', 'notes'])]
class Employee extends Model
{
    use BelongsToContractor, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => EmployeeStatus::class,
            'salary_type' => SalaryType::class,
            'joining_date' => 'date',
            'salary_amount' => 'decimal:2',
        ];
    }

    /** @return HasMany<Attendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** @return HasMany<SalaryPayment, $this> */
    public function salaryPayments(): HasMany
    {
        return $this->hasMany(SalaryPayment::class);
    }

    /** @return BelongsToMany<Work, $this> */
    public function works(): BelongsToMany
    {
        return $this->belongsToMany(Work::class, 'work_employees');
    }

    /** @return BelongsTo<\App\Models\User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class)->withTrashed();
    }

    public function hasLogin(): bool
    {
        return $this->user_id !== null;
    }

    public function activityLabel(): string
    {
        return $this->name;
    }
}
