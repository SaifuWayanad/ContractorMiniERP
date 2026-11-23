<?php

namespace App\Models;

use App\Models\Concerns\BelongsToContractor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['work_id', 'date', 'morning_count', 'evening_count', 'notes'])]
class WorkDailyWorker extends Model
{
    use BelongsToContractor;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'morning_count' => 'integer',
            'evening_count' => 'integer',
        ];
    }

    /** @return BelongsTo<Work, $this> */
    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }
}
