<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict a route to one or more roles.
 *
 * Usage: `->middleware('role:admin,hotel_manager')`
 */
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $allowed = array_values(array_filter(
            array_map(fn (string $role): ?Role => Role::tryFrom($role), $roles),
        ));

        abort_if($allowed === [], 500, 'EnsureUserHasRole was used without a valid role.');

        abort_unless(in_array($user->role, $allowed, true), 403);

        return $next($request);
    }
}
