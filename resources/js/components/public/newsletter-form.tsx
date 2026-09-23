import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

/**
 * The newsletter signup in the footer. Posts to a throttled public endpoint.
 */
export function NewsletterForm() {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        post('/newsletter', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form onSubmit={submit} className="mt-4">
            <div className="flex gap-2">
                <Input
                    type="email"
                    name="email"
                    value={data.email}
                    onChange={(event) => setData('email', event.target.value)}
                    placeholder="you@example.com"
                    aria-label="Email address"
                    className="border-sand/25 bg-white/10 text-white placeholder:text-sand/50"
                />
                <Button type="submit" variant="secondary" disabled={processing}>
                    Join
                </Button>
            </div>
            <InputError message={errors.email} className="mt-2" />
        </form>
    );
}
