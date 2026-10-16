<?php

namespace App\Models;

use App\Enums\ContractorStatus;
use App\Models\Concerns\LogsActivity;
use Database\Factories\ContractorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'company_name', 'contact_person', 'email', 'phone', 'alternate_phone',
    'address', 'city', 'state', 'country', 'postal_code',
    'website', 'tax_number', 'license_number',
])]
class Contractor extends Model
{
    /** @use HasFactory<ContractorFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContractorStatus::class,
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<ProjectLocation, $this>
     */
    public function projectLocations(): HasMany
    {
        return $this->hasMany(ProjectLocation::class);
    }

    /**
     * @return HasMany<Work, $this>
     */
    public function works(): HasMany
    {
        return $this->hasMany(Work::class);
    }

    /**
     * @return HasMany<Dealer, $this>
     */
    public function dealers(): HasMany
    {
        return $this->hasMany(Dealer::class);
    }

    /**
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        $like = '%'.$term.'%';

        return $query->where(function (Builder $query) use ($like): void {
            $query->where('company_name', 'like', $like)
                ->orWhere('contact_person', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like);
        });
    }

    public function isActive(): bool
    {
        return $this->status === ContractorStatus::Active;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path !== null ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->company_name)))
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }

    public function activityLabel(): string
    {
        return $this->company_name;
    }
}
