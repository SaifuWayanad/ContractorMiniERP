<?php

namespace App\Models;

use App\Enums\WorkPriority;
use App\Enums\WorkStatus;
use App\Models\Concerns\BelongsToContractor;
use App\Models\Concerns\LogsActivity;
use Database\Factories\WorkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'description', 'priority', 'status', 'start_date', 'due_date', 'amount'])]
class Work extends Model
{
    /** @use HasFactory<WorkFactory> */
    use BelongsToContractor, HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WorkStatus::class,
            'priority' => WorkPriority::class,
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Work $work): void {
            if ($work->isDirty('status')) {
                $work->completed_at = $work->status === WorkStatus::Completed ? now() : null;
            }
        });
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withTrashed();
    }

    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        $like = '%'.$term.'%';

        return $query->where(function (Builder $query) use ($like): void {
            $query->where('title', 'like', $like)
                ->orWhere('description', 'like', $like);
        });
    }

    /**
     * Work that still needs doing (pending, in progress or on hold).
     */
    #[Scope]
    protected function open(Builder $query): Builder
    {
        return $query->whereIn('status', WorkStatus::open());
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && ! $this->due_date->isToday()
            && in_array($this->status, WorkStatus::open(), true);
    }

    public function activityLabel(): string
    {
        return $this->title;
    }
}
