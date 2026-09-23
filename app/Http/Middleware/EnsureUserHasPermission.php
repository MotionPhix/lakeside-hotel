<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict a route to users holding every listed permission.
 *
 * Usage: `->middleware('permission:bookings.manage')`
 */
class EnsureUserHasPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $required = array_values(array_filter(
            array_map(fn (string $permission): ?Permission => Permission::tryFrom($permission), $permissions),
        ));

        abort_if($required === [], 500, 'EnsureUserHasPermission was used without a valid permission.');

        abort_unless($user->hasAllPermissions($required), 403);

        return $next($request);
    }
}
