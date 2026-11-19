<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentReturn extends Model
{
    protected $table = 'returns';

    protected $fillable = [
        'return_number',
        'customer_id',
        'project_id',
        'return_date',
        'warehouse_location_id',
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
            'return_date' => 'date',
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

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'warehouse_location_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function returnDetails(): HasMany
    {
        return $this->hasMany(ReturnDetail::class, 'return_id');
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
