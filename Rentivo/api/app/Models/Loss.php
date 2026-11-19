<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loss extends Model
{
    protected $fillable = [
        'loss_number',
        'customer_id',
        'project_id',
        'loss_date',
        'reason',
        'remarks',
        'status',
        'created_by',
        'approved_by',
        'posted_by',
        'approved_at',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'loss_date' => 'date',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function lossDetails(): HasMany
    {
        return $this->hasMany(LossDetail::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
