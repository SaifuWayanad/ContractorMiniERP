<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Models\Concerns\BelongsToContractor;
use App\Models\Concerns\LogsActivity;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'client_id', 'name', 'project_number', 'type_of_work', 'location', 'description',
    'start_date', 'expected_end_date', 'actual_end_date', 'status', 'budget',
    'brand_preferred', 'expected_qty', 'specification_details', 'notes',
    'next_meeting_at', 'next_meeting_notes',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use BelongsToContractor, HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'start_date' => 'date',
            'expected_end_date' => 'date',
            'actual_end_date' => 'date',
            'next_meeting_at' => 'datetime',
            'budget' => 'decimal:2',
            'expected_qty' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    /**
     * @return HasMany<ProjectLocation, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(ProjectLocation::class);
    }

    /**
     * The first location added, used as the project's main site in lists.
     *
     * @return HasOne<ProjectLocation, $this>
     */
    public function primaryLocation(): HasOne
    {
        return $this->hasOne(ProjectLocation::class)->oldestOfMany();
    }

    /**
     * @return HasMany<Work, $this>
     */
    public function works(): HasMany
    {
        return $this->hasMany(Work::class);
    }

    /**
     * @return HasMany<ProjectMeeting, $this>
     */
    public function meetings(): HasMany
    {
        return $this->hasMany(ProjectMeeting::class);
    }

    /**
     * @return HasMany<Purchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @return HasMany<ProjectPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(ProjectPayment::class);
    }

    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        $like = '%'.$term.'%';

        return $query->where(function (Builder $query) use ($like): void {
            $query->where('name', 'like', $like)
                ->orWhere('project_number', 'like', $like)
                ->orWhere('type_of_work', 'like', $like)
                ->orWhere('brand_preferred', 'like', $like)
                ->orWhereHas('client', fn (Builder $client) => $client->where('name', 'like', $like));
        });
    }

    /**
     * Projects that are not completed or cancelled.
     */
    #[Scope]
    protected function open(Builder $query): Builder
    {
        return $query->whereIn('status', ProjectStatus::open());
    }

    public function isOverdue(): bool
    {
        return $this->expected_end_date !== null
            && $this->expected_end_date->isPast()
            && ! $this->expected_end_date->isToday()
            && in_array($this->status, ProjectStatus::open(), true);
    }

    public function activityLabel(): string
    {
        return $this->name;
    }
}
