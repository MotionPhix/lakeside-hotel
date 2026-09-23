import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { StaffForm } from '@/components/admin/staff-form';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import users from '@/routes/admin/users';
import type { RoleOption } from '@/types';

type EditableStaff = {
    id: number;
    name: string;
    email: string;
    role: RoleOption['value'];
    phone: string | null;
    job_title: string | null;
    is_active: boolean;
    last_login_at: string | null;
    created_at: string;
    two_factor_enabled: boolean;
};

export default function EditStaff({
    user,
    roles,
}: {
    user: EditableStaff;
    roles: RoleOption[];
}) {
    return (
        <>
            <Head title={`Edit ${user.name}`} />

            <div className="flex flex-col gap-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={user.name}
                        description={`Added ${user.created_at} · Last signed in ${user.last_login_at ?? 'never'}`}
                    />
                    <Button variant="outline" asChild>
                        <Link href={users.index()}>
                            <ArrowLeft />
                            Back to staff
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <Badge variant={user.is_active ? 'secondary' : 'outline'}>
                        {user.is_active ? 'Active' : 'Deactivated'}
                    </Badge>
                    {user.two_factor_enabled && (
                        <Badge variant="outline">
                            Two-factor authentication on
                        </Badge>
                    )}
                </div>

                <Card>
                    <CardContent>
                        <StaffForm
                            roles={roles}
                            action={users.update.url(user.id)}
                            method="patch"
                            submitLabel="Save changes"
                            passwordHint="Leave both password fields empty to keep the current password."
                            initial={{
                                name: user.name,
                                email: user.email,
                                role: user.role,
                                phone: user.phone ?? '',
                                job_title: user.job_title ?? '',
                                is_active: user.is_active,
                            }}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

EditStaff.layout = {
    breadcrumbs: [
        {
            title: 'Staff',
            href: users.index(),
        },
    ],
};
