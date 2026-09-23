<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreNewsletterRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class NewsletterController extends Controller
{
    /**
     * Add an address to the newsletter list. Subscribing twice is harmless.
     */
    public function store(StoreNewsletterRequest $request): RedirectResponse
    {
        NewsletterSubscriber::subscribe(
            email: (string) $request->string('email'),
            name: $request->filled('name') ? (string) $request->string('name') : null,
            source: 'footer',
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Thank you - you are on the list.'),
        ]);

        return back();
    }
}
