<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Models\Concerns\BelongsToContractor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['employee_id', 'date', 'morning_status', 'evening_status', 'notes'])]
class Attendance extends Model
{
    use BelongsToContractor;

    protected function casts(): array
    {
        return [
            'morning_status' => AttendanceStatus::class,
            'evening_status' => AttendanceStatus::class,
            'date' => 'date',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
