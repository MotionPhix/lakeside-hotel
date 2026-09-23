<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * List the staff accounts.
     */
    public function index(Request $request): Response
    {
        $actor = $request->user();

        $search = $request->string('search')->trim()->value();
        $role = Role::tryFrom($request->string('role')->trim()->value());

        $users = User::query()
            ->search($search)
            ->when($role instanceof Role, fn (Builder $query) => $query->where('role', $role->value))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'phone' => $user->phone,
                'job_title' => $user->job_title,
                'is_active' => $user->is_active,
                'is_self' => $actor?->is($user) ?? false,
                'last_login_at' => $user->last_login_at?->diffForHumans(),
                'created_at' => $user->created_at?->toFormattedDateString(),
                'can_manage' => $actor?->canManage($user) ?? false,
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => $role?->value ?? '',
            ],
            'roles' => $this->roleOptions(),
            'stats' => [
                'total' => User::query()->count(),
                'active' => User::query()->active()->count(),
                'inactive' => User::query()->where('is_active', false)->count(),
            ],
        ]);
    }

    /**
     * Show the form for creating a staff account.
     */
    public function create(): Response
    {
        return Inertia::render('admin/users/create', [
            'roles' => $this->roleOptions(),
        ]);
    }

    /**
     * Store a newly created staff account.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        // `email_verified_at` is deliberately not mass assignable, so it is set
        // directly: an administrator creating the account vouches for the address.
        $user = new User($request->validated());
        $user->email_verified_at = now();
        $user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':name has been added to the team.', ['name' => $user->name]),
        ]);

        return to_route('admin.users.index');
    }

    /**
     * Show the form for editing a staff account.
     */
    public function edit(Request $request, User $user): Response
    {
        $this->ensureCanManage($request, $user);

        return Inertia::render('admin/users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'phone' => $user->phone,
                'job_title' => $user->job_title,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at?->diffForHumans(),
                'created_at' => $user->created_at?->toFormattedDateString(),
                'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
            ],
            'roles' => $this->roleOptions(),
        ]);
    }

    /**
     * Update a staff account.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->ensureCanManage($request, $user);

        $attributes = $request->validated();

        if (blank($attributes['password'] ?? null)) {
            unset($attributes['password']);
        }

        $user->fill($attributes);

        if ($user->isDirty('email')) {
            // Staff accounts are created and vouched for by an administrator,
            // so a corrected address stays verified.
            $user->email_verified_at = now();
        }

        $user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':name has been updated.', ['name' => $user->name]),
        ]);

        return to_route('admin.users.index');
    }

    /**
     * Activate or deactivate a staff account.
     */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanManage($request, $user);

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $user->is_active
                ? __(':name can sign in again.', ['name' => $user->name])
                : __(':name has been deactivated.', ['name' => $user->name]),
        ]);

        return back();
    }

    /**
     * Remove a staff account.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanManage($request, $user);

        $user->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':name has been removed.', ['name' => $user->name]),
        ]);

        return to_route('admin.users.index');
    }

    /**
     * The roles the signed in administrator is allowed to hand out.
     *
     * @return list<array{value: string, label: string, description: string, level: int}>
     */
    private function roleOptions(): array
    {
        $actor = request()->user();

        $assignable = array_values(array_filter(
            Role::assignable(),
            fn (Role $role): bool => $actor instanceof User
                && ($actor->role === Role::SystemAdmin || $role->level() < $actor->role->level()),
        ));

        return array_map(fn (Role $role): array => [
            'value' => $role->value,
            'label' => $role->label(),
            'description' => $role->description(),
            'level' => $role->level(),
        ], $assignable);
    }

    /**
     * Refuse an operation the signed in administrator is not senior enough for.
     */
    private function ensureCanManage(Request $request, User $user): void
    {
        abort_unless($request->user()?->canManage($user) ?? false, 403);
    }
}
