<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Enums\Role;
use App\Listeners\RecordSuccessfulLogin;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Bind every permission to a gate so `can()` works in controllers and views.
     */
    protected function configureAuthorization(): void
    {
        Event::listen(Login::class, RecordSuccessfulLogin::class);

        Gate::before(function (?User $user, string $ability): ?bool {
            if ($user instanceof User && $user->role === Role::SystemAdmin && Permission::tryFrom($ability) instanceof Permission) {
                return true;
            }

            return null;
        });

        foreach (Permission::cases() as $permission) {
            Gate::define(
                $permission->value,
                fn (User $user): bool => $user->hasPermission($permission),
            );
        }
    }
}
