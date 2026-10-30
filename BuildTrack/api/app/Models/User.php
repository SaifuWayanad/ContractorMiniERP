<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['username', 'password_hash', 'full_name', 'email', 'role', 'is_active'])]
#[Hidden(['password_hash'])]
class User extends Authenticatable
{
    use HasApiTokens;

    public const ADMIN = 'Admin';
    public const CONTRACTOR = 'Contractor';

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ADMIN;
    }

    /** @return HasMany<Project, $this> */
    public function assignedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'assigned_user_id');
    }
}
