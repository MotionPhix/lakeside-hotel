import { useForm } from '@inertiajs/react';
import { Send } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const fieldClass =
    'border-navy/15 h-11 w-full rounded-md border bg-white px-3 text-sm text-navy outline-none transition-colors placeholder:text-navy/35 focus-visible:border-lake focus-visible:ring-2 focus-visible:ring-lake/30';

/**
 * The website contact form. Writes an inquiry the front desk can answer in the
 * dashboard.
 */
export function ContactForm() {
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
        recentlySuccessful,
    } = useForm({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
        preferred_date: '',
        guests_count: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        post('/contact', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form onSubmit={submit} className="grid gap-5">
            <div className="grid gap-5 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="name">Your name</Label>
                    <Input
                        id="name"
                        name="name"
                        value={data.name}
                        onChange={(event) =>
                            setData('name', event.target.value)
                        }
                        className={fieldClass}
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        value={data.email}
                        onChange={(event) =>
                            setData('email', event.target.value)
                        }
                        className={fieldClass}
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="phone">Phone (optional)</Label>
                    <Input
                        id="phone"
                        name="phone"
                        value={data.phone}
                        onChange={(event) =>
                            setData('phone', event.target.value)
                        }
                        className={fieldClass}
                    />
                    <InputError message={errors.phone} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="guests_count">Guests (optional)</Label>
                    <Input
                        id="guests_count"
                        name="guests_count"
                        type="number"
                        min={1}
                        value={data.guests_count}
                        onChange={(event) =>
                            setData('guests_count', event.target.value)
                        }
                        className={fieldClass}
                    />
                    <InputError message={errors.guests_count} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="preferred_date">
                        Preferred date (optional)
                    </Label>
                    <Input
                        id="preferred_date"
                        name="preferred_date"
                        type="date"
                        value={data.preferred_date}
                        onChange={(event) =>
                            setData('preferred_date', event.target.value)
                        }
                        className={fieldClass}
                    />
                    <InputError message={errors.preferred_date} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="subject">Subject</Label>
                    <Input
                        id="subject"
                        name="subject"
                        value={data.subject}
                        onChange={(event) =>
                            setData('subject', event.target.value)
                        }
                        className={fieldClass}
                    />
                    <InputError message={errors.subject} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="message">How can we help?</Label>
                <textarea
                    id="message"
                    name="message"
                    rows={5}
                    value={data.message}
                    onChange={(event) => setData('message', event.target.value)}
                    className="w-full rounded-md border border-navy/15 bg-white px-3 py-2.5 text-sm text-navy transition-colors outline-none placeholder:text-navy/35 focus-visible:border-lake focus-visible:ring-2 focus-visible:ring-lake/30"
                    placeholder="Dates, group size, anything else we should know."
                />
                <InputError message={errors.message} />
            </div>

            <div className="flex flex-wrap items-center gap-4">
                <Button type="submit" size="lg" disabled={processing}>
                    <Send />
                    Send message
                </Button>
                {recentlySuccessful && (
                    <p className="text-sm text-lake">
                        Thank you - we will be in touch shortly.
                    </p>
                )}
            </div>
        </form>
    );
}
