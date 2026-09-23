<?php

namespace App\Rules;

use App\Enums\Role;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * A staff role may only be granted by somebody senior enough to grant it.
 */
class AssignableRole implements ValidationRule
{
    public function __construct(private readonly ?User $actor) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $role = $value instanceof Role ? $value : Role::tryFrom((string) $value);

        if (! $role instanceof Role) {
            $fail('The selected role is not valid.');

            return;
        }

        if (! $role->isStaff()) {
            $fail('Staff accounts must be given a staff role.');

            return;
        }

        if (! $this->actor instanceof User || ! $this->actor->role->isStaff()) {
            $fail('You are not allowed to manage staff accounts.');

            return;
        }

        if ($this->actor->role === Role::SystemAdmin) {
            return;
        }

        if ($role->level() >= $this->actor->role->level()) {
            $fail("You cannot assign the {$role->label()} role.");
        }
    }
}
