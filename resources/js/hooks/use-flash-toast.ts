import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

/**
 * Shows the messages the back end sends.
 *
 * This is the *only* place in the front end that may raise a toast, and it is
 * raised on behalf of a controller rather than by a click: the message arrives as
 * Inertia flash data on the response that follows the request, so a toast is never
 * a guess about what happened, it is a report of what the server did.
 *
 * The front end is not allowed to invent one. A component that raises its own
 * toast is claiming an action succeeded without being told so, and the two
 * sources then disagree the moment the server refuses - the toast says it worked
 * and the error sits under a field saying it did not. A test holds that line: see
 * tests/Feature/ToastContractTest.php.
 *
 * Mount it exactly once, alongside the Toaster. Every mount is another listener,
 * and two listeners are two copies of every message.
 */
export function useFlashToast(): void {
    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const data = flash?.toast as FlashToast | undefined;

            if (!data) {
                return;
            }

            toast[data.type](data.message);
        });
    }, []);
}
