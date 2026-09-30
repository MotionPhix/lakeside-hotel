import { Head } from '@inertiajs/react';
import { SettingSection } from '@/components/admin/setting-section';
import Heading from '@/components/heading';
import system from '@/routes/admin/system';
import type { SettingSection as SettingSectionData } from '@/types';

/**
 * The hotel's own settings.
 *
 * The screen is generated from the server's declarations, so what is editable
 * here and what the server accepts cannot drift apart, and a value nobody
 * declared cannot be written.
 *
 * The sections that decide what a guest is charged come first, since those are
 * the settings somebody actually comes here to change - a rate that has moved is
 * a rate that is wrong for as long as it takes to find it.
 */
export default function SystemSettings({
    sections,
}: {
    sections: SettingSectionData[];
}) {
    return (
        <>
            <Head title="System settings" />

            <div className="flex flex-col gap-6">
                <Heading
                    title="System settings"
                    description="The tax guests are charged, the terms they book under, and the details the site publishes. Prices across the website follow these rates."
                />

                {sections.map((section) => (
                    <SettingSection key={section.key} section={section} />
                ))}
            </div>
        </>
    );
}

SystemSettings.layout = {
    breadcrumbs: [
        {
            title: 'System settings',
            href: system.index(),
        },
    ],
};
