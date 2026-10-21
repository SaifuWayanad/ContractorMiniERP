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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'client_id', 'dealer_id', 'name', 'project_number', 'type_of_work', 'description',
    'start_date', 'expected_end_date', 'actual_end_date', 'status', 'budget',
    'brand_preferred', 'expected_qty', 'expected_maturity_date', 'specification_details', 'notes',
    'next_meeting_at', 'next_meeting_notes',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use BelongsToContractor, HasFactory, LogsActivity, SoftDeletes;

    /**
     * Projects maturing within this many days count as "maturing soon".
     */
    public const MATURING_SOON_DAYS = 30;

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
            'expected_maturity_date' => 'date',
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
     * @return BelongsTo<Dealer, $this>
     */
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class)->withTrashed();
    }

    /**
     * @return BelongsToMany<Dealer, $this>
     */
    public function dealers(): BelongsToMany
    {
        return $this->belongsToMany(Dealer::class, 'project_dealer')->withTimestamps();
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

    /**
     * Badge text and CSS class describing how close the expected maturity date is.
     *
     * @return array{label: string, class: string}
     */
    public function maturityStatus(): array
    {
        if ($this->expected_maturity_date === null) {
            return ['label' => 'No date', 'class' => 'badge-soft-secondary'];
        }

        $daysLeft = (int) today()->diffInDays($this->expected_maturity_date, false);

        return match (true) {
            $daysLeft < 0 => ['label' => 'Past due', 'class' => 'badge-soft-danger'],
            $daysLeft === 0 => ['label' => 'Today', 'class' => 'badge-soft-warning'],
            $daysLeft <= self::MATURING_SOON_DAYS => ['label' => "In {$daysLeft}d", 'class' => 'badge-soft-warning'],
            default => ['label' => "In {$daysLeft}d", 'class' => 'badge-soft-info'],
        };
    }

    public function activityLabel(): string
    {
        return $this->name;
    }
}
