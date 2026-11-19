<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectClosureDetail extends Model
{
    protected $fillable = [
        'project_closure_id',
        'product_id',
        'dispatched_quantity',
        'returned_quantity',
        'transferred_out_quantity',
        'transferred_in_quantity',
        'lost_quantity',
        'written_off_quantity',
        'outstanding_quantity',
        'resolution_status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'dispatched_quantity' => 'decimal:3',
            'returned_quantity' => 'decimal:3',
            'transferred_out_quantity' => 'decimal:3',
            'transferred_in_quantity' => 'decimal:3',
            'lost_quantity' => 'decimal:3',
            'written_off_quantity' => 'decimal:3',
            'outstanding_quantity' => 'decimal:3',
        ];
    }

    public function projectClosure(): BelongsTo
    {
        return $this->belongsTo(ProjectClosure::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
