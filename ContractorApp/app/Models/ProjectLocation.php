<?php

namespace App\Models;

use App\Models\Concerns\BelongsToContractor;
use App\Models\Concerns\LogsActivity;
use Database\Factories\ProjectLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'address', 'city', 'district', 'state', 'country', 'postal_code',
    'latitude', 'longitude', 'contact_person', 'contact_phone', 'notes',
])]
class ProjectLocation extends Model
{
    /** @use HasFactory<ProjectLocationFactory> */
    use BelongsToContractor, HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        $like = '%'.$term.'%';

        return $query->where(function (Builder $query) use ($like): void {
            $query->where('name', 'like', $like)
                ->orWhere('address', 'like', $like)
                ->orWhere('city', 'like', $like)
                ->orWhere('district', 'like', $like)
                ->orWhere('contact_person', 'like', $like);
        });
    }

    public function shortAddress(): string
    {
        return collect([$this->city, $this->district, $this->state])->filter()->unique()->implode(', ');
    }

    public function activityLabel(): string
    {
        return $this->name;
    }
}
