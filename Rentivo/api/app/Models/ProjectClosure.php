<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectClosure extends Model
{
    protected $fillable = [
        'closure_number',
        'project_id',
        'customer_id',
        'initiated_date',
        'closure_date',
        'status',
        'total_dispatched_quantity',
        'total_returned_quantity',
        'total_transferred_out_quantity',
        'total_lost_quantity',
        'total_damaged_quantity',
        'total_invoiced_amount',
        'total_paid_amount',
        'financial_outstanding',
        'closure_notes',
        'created_by',
        'approved_by',
        'closed_by',
        'approved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'initiated_date' => 'date',
            'closure_date' => 'date',
            'total_dispatched_quantity' => 'decimal:3',
            'total_returned_quantity' => 'decimal:3',
            'total_transferred_out_quantity' => 'decimal:3',
            'total_lost_quantity' => 'decimal:3',
            'total_damaged_quantity' => 'decimal:3',
            'total_invoiced_amount' => 'decimal:2',
            'total_paid_amount' => 'decimal:2',
            'financial_outstanding' => 'decimal:2',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function projectClosureDetails(): HasMany
    {
        return $this->hasMany(ProjectClosureDetail::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
