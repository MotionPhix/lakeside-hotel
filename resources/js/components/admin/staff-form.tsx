import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { RoleName, RoleOption } from '@/types';

export type StaffFormValues = {
    name: string;
    email: string;
    role: RoleName | '';
    phone: string;
    job_title: string;
    is_active: boolean;
    password: string;
    password_confirmation: string;
};

const emptyValues: StaffFormValues = {
    name: '',
    email: '',
    role: '',
    phone: '',
    job_title: '',
    is_active: true,
    password: '',
    password_confirmation: '',
};

export function StaffForm({
    roles,
    action,
    method,
    submitLabel,
    passwordHint,
    initial,
}: {
    roles: RoleOption[];
    action: string;
    method: 'post' | 'patch';
    submitLabel: string;
    passwordHint: string;
    initial?: Partial<StaffFormValues>;
}) {
    const { data, setData, post, patch, processing, errors } =
        useForm<StaffFormValues>({ ...emptyValues, ...initial });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (method === 'post') {
            post(action);

            return;
        }

        patch(action);
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid gap-6 md:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="name">Full name</Label>
                    <Input
                        id="name"
                        value={data.name}
                        autoComplete="name"
                        onChange={(e) => setData('name', e.target.value)}
                        aria-invalid={errors.name ? true : undefined}
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        value={data.email}
                        autoComplete="email"
                        onChange={(e) => setData('email', e.target.value)}
                        aria-invalid={errors.email ? true : undefined}
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="job_title">Job title</Label>
                    <Input
                        id="job_title"
                        value={data.job_title}
                        placeholder="Front Desk Supervisor"
                        onChange={(e) => setData('job_title', e.target.value)}
                        aria-invalid={errors.job_title ? true : undefined}
                    />
                    <InputError message={errors.job_title} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="phone">Phone</Label>
                    <Input
                        id="phone"
                        value={data.phone}
                        placeholder="+265 99 123 4567"
                        onChange={(e) => setData('phone', e.target.value)}
                        aria-invalid={errors.phone ? true : undefined}
                    />
                    <InputError message={errors.phone} />
                </div>

                <div className="grid gap-2 md:col-span-2">
                    <Label htmlFor="role">Role</Label>
                    <Select
                        value={data.role}
                        onValueChange={(value) =>
                            setData('role', value as RoleName)
                        }
                    >
                        <SelectTrigger
                            id="role"
                            className="w-full"
                            aria-invalid={errors.role ? true : undefined}
                        >
                            <SelectValue placeholder="Choose a role" />
                        </SelectTrigger>
                        <SelectContent>
                            {roles.map((role) => (
                                <SelectItem key={role.value} value={role.value}>
                                    {role.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {data.role && (
                        <p className="text-sm text-muted-foreground">
                            {
                                roles.find((role) => role.value === data.role)
                                    ?.description
                            }
                        </p>
                    )}
                    <InputError message={errors.role} />
                </div>
            </div>

            <div className="grid gap-6 border-t pt-6 md:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="password">
                        {method === 'post' ? 'Password' : 'New password'}
                    </Label>
                    <Input
                        id="password"
                        type="password"
                        value={data.password}
                        autoComplete="new-password"
                        onChange={(e) => setData('password', e.target.value)}
                        aria-invalid={errors.password ? true : undefined}
                    />
                    <p className="text-xs text-muted-foreground">
                        {passwordHint}
                    </p>
                    <InputError message={errors.password} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">
                        Confirm password
                    </Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        autoComplete="new-password"
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                    />
                    <InputError message={errors.password_confirmation} />
                </div>
            </div>

            <div className="flex items-start gap-3 rounded-lg border p-4">
                <input
                    id="is_active"
                    type="checkbox"
                    checked={data.is_active}
                    onChange={(e) => setData('is_active', e.target.checked)}
                    className="mt-0.5 size-4 rounded border-input accent-primary"
                />
                <div className="grid gap-1">
                    <Label htmlFor="is_active">Account is active</Label>
                    <p className="text-sm text-muted-foreground">
                        Deactivated accounts keep their history but cannot sign
                        in.
                    </p>
                </div>
            </div>

            <div className="flex items-center gap-3">
                <Button type="submit" disabled={processing}>
                    {submitLabel}
                </Button>
            </div>
        </form>
    );
}
