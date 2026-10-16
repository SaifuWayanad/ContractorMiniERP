<?php

namespace App\Models;

use App\Models\Concerns\BelongsToContractor;
use Database\Factories\DealerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'city', 'phone'])]
class Dealer extends Model
{
    /** @use HasFactory<DealerFactory> */
    use BelongsToContractor, HasFactory, SoftDeletes;

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
