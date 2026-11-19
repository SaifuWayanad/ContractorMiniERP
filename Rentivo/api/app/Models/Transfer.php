<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transfer extends Model
{
    protected $fillable = [
        'transfer_number',
        'from_project_id',
        'to_project_id',
        'from_customer_id',
        'to_customer_id',
        'transfer_date',
        'vehicle_id',
        'operator_id',
        'status',
        'remarks',
        'created_by',
        'approved_by',
        'posted_by',
        'approved_at',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function fromProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'from_project_id');
    }

    public function toProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'to_project_id');
    }

    public function fromCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'from_customer_id');
    }

    public function toCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'to_customer_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function transferDetails(): HasMany
    {
        return $this->hasMany(TransferDetail::class);
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
