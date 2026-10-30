<?php

namespace App\Models;

use App\Models\Concerns\BelongsToContractor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['client_id', 'description', 'purpose', 'location', 'meeting_date', 'next_meeting_date'])]
class Visit extends Model
{
    use BelongsToContractor, SoftDeletes;

    protected function casts(): array
    {
        return [
            'meeting_date'      => 'date',
            'next_meeting_date' => 'date',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
