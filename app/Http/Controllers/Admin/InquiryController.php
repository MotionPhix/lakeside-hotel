<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Exceptions\InquiryNotActionable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InquiryIndexRequest;
use App\Http\Requests\Admin\RespondToInquiryRequest;
use App\Http\Requests\Admin\UpdateInquiryStatusRequest;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Enquiries from the website: the contact form, event requests, group bookings.
 *
 * A queue rather than a mailbox. Somebody has to own an enquiry before it can be
 * answered, so the first move is to claim it; the reply is then recorded against
 * it, which is what makes the record worth keeping - the next person to open it
 * can see what was said without searching anybody's sent items.
 *
 * Closing one and filing it as spam are deliberately different. Spam is not a
 * closed enquiry that nobody answered, it is one that was never real, and the
 * hotel's own counts depend on the difference.
 */
class InquiryController extends Controller
{
    /**
     * The queue, with what nobody has dealt with at the top.
     */
    public function index(InquiryIndexRequest $request): Response
    {
        $search = $request->search();
        $status = $request->status();
        $type = $request->type();

        $inquiries = Inquiry::query()
            ->with('assignee')
            ->when($search !== '', fn (Builder $query) => $query->search($search))
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($type !== null, fn (Builder $query) => $query->where('type', $type))
            /*
             * Open enquiries first, then the newest of everything. The queue is
             * worked from the top, and an enquiry from this morning matters more
             * than one that was filed a fortnight ago.
             */
            ->orderByRaw(
                'CASE WHEN status IN (?, ?) THEN 0 ELSE 1 END',
                [InquiryStatus::New->value, InquiryStatus::InProgress->value],
            )
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Inquiry $inquiry): array => $this->row($inquiry));

        return Inertia::render('admin/inquiries/index', [
            'inquiries' => $inquiries,
            'filters' => $request->summary(),
            'totals' => [
                'matching' => $inquiries->total(),
                'open' => Inquiry::query()->open()->count(),
                // Nobody has picked it up, so nothing will happen to it.
                'unclaimed' => Inquiry::query()->open()->whereNull('assigned_to')->count(),
            ],
            'statuses' => InquiryStatus::options(),
            // The kinds the desk can filter by, taken from the enum rather than
            // spelled out in the page: a kind added on the server appears in the
            // filter without anybody remembering to add it.
            'types' => InquiryType::options(),
        ]);
    }

    /**
     * One enquiry: what was asked, who has it, and what was said back.
     */
    public function show(Inquiry $inquiry): Response
    {
        $inquiry->load('assignee');

        return Inertia::render('admin/inquiries/show', [
            'inquiry' => [
                'id' => $inquiry->getKey(),
                'name' => $inquiry->name,
                'email' => $inquiry->email,
                'phone' => $inquiry->phone,
                'subject' => $inquiry->subject,
                'message' => $inquiry->message,
                'type' => $inquiry->type->value,
                'type_label' => $inquiry->type->label(),
                'status' => $inquiry->status->value,
                'status_label' => $inquiry->status->label(),
                'status_variant' => $inquiry->status->variant(),
                'preferred_date' => $inquiry->preferred_date?->toDateString(),
                'guests_count' => $inquiry->guests_count,
                'source_page' => $inquiry->source_page,
                'received_at' => $inquiry->created_at?->toDateString(),
                'assigned_to' => $inquiry->assigned_to,
                'assigned_name' => $inquiry->assignee?->name,
                'response' => $inquiry->response,
                'responded_at' => $inquiry->responded_at?->toDateString(),
                /*
                 * The one definition of "somebody can still do something about
                 * this", shared with the refusals below rather than repeated in
                 * the page: a requirement that lives in two places is a
                 * requirement that will disagree with itself.
                 */
                'actionable' => $this->actionable($inquiry),
            ],
        ]);
    }

    /**
     * Take an enquiry on.
     */
    public function claim(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        if (! $this->actionable($inquiry)) {
            return $this->refuse($inquiry);
        }

        if ($inquiry->assigned_to === $user->getKey()) {
            return $this->done(__('Already yours.'));
        }

        $previous = $inquiry->assigned_to === null ? null : $inquiry->assignee;

        $inquiry->assignee()->associate($user);

        // Picking it up is the desk saying they are on it, so it stops reading as
        // new to everybody else looking at the same queue.
        if ($inquiry->status === InquiryStatus::New) {
            $inquiry->status = InquiryStatus::InProgress;
        }

        $inquiry->save();

        return $this->done($previous instanceof User
            ? __('Picked up from :name.', ['name' => $previous->name])
            : __('Enquiry claimed.'));
    }

    /**
     * Record the reply that was sent.
     *
     * Written against the enquiry rather than emailed from here: what is being
     * kept is the fact and the wording of the answer, so that whoever opens this
     * next does not have to ask around for it.
     */
    public function respond(RespondToInquiryRequest $request, Inquiry $inquiry): RedirectResponse
    {
        if (! $this->actionable($inquiry)) {
            return $this->refuse($inquiry);
        }

        $again = $inquiry->hasResponse();

        $inquiry->markResponded($request->response(), $request->user());

        return $this->done($again ? __('Reply updated.') : __('Reply recorded.'));
    }

    /**
     * File an enquiry: closed, spam, or opened back up.
     */
    public function updateStatus(UpdateInquiryStatusRequest $request, Inquiry $inquiry): RedirectResponse
    {
        $status = $request->status();

        // An enquiry marked responded with nothing written on it would be a record
        // of an answer nobody can read.
        if ($status === InquiryStatus::Responded && ! $inquiry->hasResponse()) {
            throw ValidationException::withMessages([
                'status' => __('Record the reply before filing this as responded.'),
            ]);
        }

        $inquiry->status = $status;
        $inquiry->save();

        return $this->done(match ($status) {
            InquiryStatus::Closed => __('Enquiry closed.'),
            InquiryStatus::Spam => __('Enquiry filed as spam.'),
            InquiryStatus::Responded => __('Enquiry marked responded.'),
            default => __('Enquiry reopened.'),
        });
    }

    /**
     * Whether the desk can still do something about this enquiry.
     */
    private function actionable(Inquiry $inquiry): bool
    {
        return ! in_array($inquiry->status, [InquiryStatus::Spam, InquiryStatus::Closed], true);
    }

    /**
     * Say why nothing happened.
     */
    private function refuse(Inquiry $inquiry): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'error',
            'message' => $inquiry->status === InquiryStatus::Spam
                ? InquiryNotActionable::spam()->getMessage()
                : InquiryNotActionable::closed()->getMessage(),
        ]);

        return back();
    }

    /**
     * Say what happened.
     */
    private function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $message,
        ]);

        return back();
    }

    /**
     * One enquiry as the queue lists it.
     *
     * @return array<string, mixed>
     */
    private function row(Inquiry $inquiry): array
    {
        return [
            'id' => $inquiry->getKey(),
            'name' => $inquiry->name,
            'email' => $inquiry->email,
            'subject' => $inquiry->subject,
            'excerpt' => str($inquiry->message)->limit(90)->value(),
            'type_label' => $inquiry->type->label(),
            'status' => $inquiry->status->value,
            'status_label' => $inquiry->status->label(),
            'status_variant' => $inquiry->status->variant(),
            'received_at' => $inquiry->created_at?->toDateString(),
            'assigned_name' => $inquiry->assignee?->name,
            'responded_at' => $inquiry->responded_at?->toDateString(),
        ];
    }
}
