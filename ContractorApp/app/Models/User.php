<?php

namespace App\Models;

use App\Enums\RoleType;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * contractor_id, role_id and status are deliberately not mass assignable; they are set explicitly by
 * admin screens so a user can never change their own contractor, role or status through a form.
 */
#[Fillable(['name', 'username', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Contractor, $this>
     */
    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        $like = '%'.$term.'%';

        return $query->where(fn (Builder $query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like));
    }

    /**
     * Permission names granted through the user's role, loaded once per request.
     *
     * @return list<string>
     */
    public function permissionNames(): array
    {
        $this->loadMissing('role.permissions');

        return $this->role?->permissions->pluck('name')->all() ?? [];
    }

    public function hasPermission(string $permission): bool
    {
        return $this->isSuperAdmin() || in_array($permission, $this->permissionNames(), true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->contractor_id === null && $this->role?->isSuperAdmin() === true;
    }

    /**
     * Internal admin/management user (no contractor, admin-type role).
     */
    public function isAdmin(): bool
    {
        return $this->contractor_id === null && $this->role?->type === RoleType::Admin;
    }

    public function isContractorUser(): bool
    {
        return $this->contractor_id !== null && $this->role?->type === RoleType::Contractor;
    }

    /**
     * Whether the account may sign in: the user is active and, for contractor users, so is their contractor.
     */
    public function canSignIn(): bool
    {
        if ($this->status !== UserStatus::Active || $this->role === null) {
            return false;
        }

        if ($this->contractor_id === null) {
            return $this->isAdmin();
        }

        return $this->isContractorUser() && $this->contractor?->isActive() === true;
    }

    public function homeRoute(): string
    {
        return $this->isAdmin() ? route('admin.dashboard') : route('dashboard');
    }

    public function firstName(): string
    {
        return strtok($this->name, ' ') ?: $this->name;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }
}
