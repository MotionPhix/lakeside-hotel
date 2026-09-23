import { usePage } from '@inertiajs/react';
import type { Permission, RoleName, User } from '@/types';

/**
 * The signed in staff account, or null for a visitor.
 */
export function useAuthUser(): User | null {
    return usePage().props.auth.user;
}

/**
 * Permission helpers driven by the `auth.user.permissions` shared prop, so the
 * interface only ever offers what the signed in role is allowed to do.
 */
export function usePermissions(): {
    user: User | null;
    permissions: string[];
    can: (permission: Permission) => boolean;
    canAny: (permissions: Permission[]) => boolean;
    hasRole: (...roles: RoleName[]) => boolean;
} {
    const user = useAuthUser();
    const permissions = user?.permissions ?? [];

    return {
        user,
        permissions,
        can: (permission: Permission) => permissions.includes(permission),
        canAny: (candidates: Permission[]) =>
            candidates.some((permission) => permissions.includes(permission)),
        hasRole: (...roles: RoleName[]) =>
            user !== null && roles.includes(user.role),
    };
}
