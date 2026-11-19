<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    protected $fillable = [
        'customer_id',
        'code',
        'name',
        'site_address',
        'contact_person',
        'contact_phone',
        'start_date',
        'expected_end_date',
        'actual_closure_date',
        'status',
        'default_location_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expected_end_date' => 'date',
            'actual_closure_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function defaultLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'default_location_id');
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class);
    }

    public function equipmentReturns(): HasMany
    {
        return $this->hasMany(EquipmentReturn::class);
    }

    public function transfersOut(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_project_id');
    }

    public function transfersIn(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_project_id');
    }

    public function rentalPeriods(): HasMany
    {
        return $this->hasMany(RentalPeriod::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function losses(): HasMany
    {
        return $this->hasMany(Loss::class);
    }

    public function damages(): HasMany
    {
        return $this->hasMany(Damage::class);
    }

    public function projectClosure(): HasOne
    {
        return $this->hasOne(ProjectClosure::class);
    }

    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class, 'location_id')
            ->where('location_type', 'PROJECT');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
