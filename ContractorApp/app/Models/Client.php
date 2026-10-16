<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Models\Concerns\BelongsToContractor;
use App\Models\Concerns\LogsActivity;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'company', 'contact_person', 'email', 'phone',
    'address', 'city', 'state', 'country', 'postal_code', 'status', 'notes',
])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use BelongsToContractor, HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ClientStatus::class,
        ];
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<Work, $this>
     */
    public function works(): HasMany
    {
        return $this->hasMany(Work::class);
    }

    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        $like = '%'.$term.'%';

        return $query->where(function (Builder $query) use ($like): void {
            $query->where('name', 'like', $like)
                ->orWhere('company', 'like', $like)
                ->orWhere('contact_person', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like);
        });
    }

    public function activityLabel(): string
    {
        return $this->name;
    }
}
