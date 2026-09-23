<?php

namespace App\Models;

use App\Enums\Permission;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property Role $role
 * @property string|null $phone
 * @property string|null $job_title
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'role', 'phone', 'job_title', 'is_active'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Only active accounts may sign in.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Whether the account can reach the dashboard at all.
     */
    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    /**
     * Whether the account holds the given permission.
     */
    public function hasPermission(Permission $permission): bool
    {
        return $this->is_active && $this->role->hasPermission($permission);
    }

    /**
     * Whether the account holds every given permission.
     *
     * @param  array<int, Permission>  $permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        return array_all(
            $permissions,
            fn (Permission $permission): bool => $this->hasPermission($permission),
        );
    }

    /**
     * Whether the account holds at least one of the given permissions.
     *
     * @param  array<int, Permission>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return array_any(
            $permissions,
            fn (Permission $permission): bool => $this->hasPermission($permission),
        );
    }

    /**
     * The value list of the account's permissions, for sharing with the front end.
     *
     * @return list<string>
     */
    public function permissionValues(): array
    {
        return array_map(
            fn (Permission $permission): string => $permission->value,
            $this->role->permissions(),
        );
    }

    /**
     * Whether this account may manage the given account.
     */
    public function canManage(User $target): bool
    {
        if (! $this->is_active || $this->is($target) || ! $this->isStaff()) {
            return false;
        }

        if ($this->role === Role::SystemAdmin) {
            return true;
        }

        return $target->role->level() < $this->role->level();
    }

    /**
     * Limit the query to active accounts.
     *
     * @param  Builder<User>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Limit the query to accounts that hold a staff role.
     *
     * @param  Builder<User>  $query
     */
    public function scopeStaff(Builder $query): void
    {
        $query->whereIn('role', array_map(fn (Role $role): string => $role->value, Role::staff()));
    }

    /**
     * Limit the query to accounts matching a name or email search term.
     *
     * @param  Builder<User>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('job_title', 'like', "%{$term}%");
        });
    }
}
