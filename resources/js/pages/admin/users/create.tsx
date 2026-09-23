import { Head } from '@inertiajs/react';
import { StaffForm } from '@/components/admin/staff-form';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import users from '@/routes/admin/users';
import type { RoleOption } from '@/types';

export default function CreateStaff({ roles }: { roles: RoleOption[] }) {
    return (
        <>
            <Head title="Add staff member" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Add a staff member"
                    description="The role you choose decides which dashboard modules this person can open."
                />

                <Card>
                    <CardContent>
                        <StaffForm
                            roles={roles}
                            action={users.store.url()}
                            method="post"
                            submitLabel="Add staff member"
                            passwordHint="At least 8 characters. Share it securely and ask them to change it after their first sign in."
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CreateStaff.layout = {
    breadcrumbs: [
        {
            title: 'Staff',
            href: users.index(),
        },
        {
            title: 'Add',
            href: users.create(),
        },
    ],
};
