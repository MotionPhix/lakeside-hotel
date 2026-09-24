<?php

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\Role;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/*
 * The enquiry queue.
 *
 * An enquiry is only worth keeping if the record shows who answered it and what
 * they said, so these mostly pin the states rather than the screens: who owns it,
 * what makes it responded, and which refusals keep a closed or spam enquiry from
 * being worked on as though it were live.
 */

beforeEach(function (): void {
    $this->seed();

    $this->manager = User::factory()->role(Role::HotelManager)->create();
    $this->reception = User::factory()->role(Role::Reception)->create();
    $this->marketing = User::factory()->role(Role::Marketing)->create();

    $this->inquiry = Inquiry::factory()->create([
        'name' => 'Chikondi Phiri',
        'email' => 'chikondi.phiri@example.com',
        'phone' => '0999 123 456',
        'subject' => 'Two rooms in April',
        'message' => 'Do you have two doubles for the first weekend of April?',
        'type' => InquiryType::Booking,
        'status' => InquiryStatus::New,
    ]);

    $this->open = fn (?Inquiry $inquiry = null) => $this->actingAs($this->manager)
        ->get(route('admin.inquiries.show', ($inquiry ?? $this->inquiry)->getKey()));

    $this->queue = fn (array $query = []) => $this->actingAs($this->manager)
        ->get(route('admin.inquiries.index', $query));
});

test('the queue is readable by the roles that keep it', function () {
    foreach ([$this->manager, $this->reception] as $actor) {
        $this->actingAs($actor)
            ->get(route('admin.inquiries.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/inquiries/index')
                ->has('inquiries.data')
                ->has('filters.search')
                ->has('statuses')
                ->has('types')
                ->has('totals.open')
                ->has('totals.unclaimed'));
    }
});

test('a role without the permission can neither read an enquiry nor act on it', function () {
    $this->actingAs($this->marketing)
        ->get(route('admin.inquiries.index'))
        ->assertForbidden();

    $this->actingAs($this->marketing)
        ->get(route('admin.inquiries.show', $this->inquiry->getKey()))
        ->assertForbidden();

    $this->actingAs($this->marketing)
        ->post(route('admin.inquiries.claim', $this->inquiry->getKey()))
        ->assertForbidden();

    $this->actingAs($this->marketing)
        ->post(route('admin.inquiries.respond', $this->inquiry->getKey()), [
            'response' => 'Not allowed to say this.',
        ])
        ->assertForbidden();

    $this->actingAs($this->marketing)
        ->patch(route('admin.inquiries.status', $this->inquiry->getKey()), [
            'status' => InquiryStatus::Closed->value,
        ])
        ->assertForbidden();

    expect($this->inquiry->refresh()->response)->toBeNull()
        ->and($this->inquiry->status)->toBe(InquiryStatus::New);
});

test('the queue can be narrowed by name, email or subject', function () {
    ($this->queue)(['search' => 'Chikondi'])
        ->assertInertia(fn ($page) => $page
            ->has('inquiries.data', 1)
            ->where('inquiries.data.0.id', $this->inquiry->getKey()));

    ($this->queue)(['search' => 'chikondi.phiri@example.com'])
        ->assertInertia(fn ($page) => $page
            ->has('inquiries.data', 1));

    ($this->queue)(['search' => 'two rooms in april'])
        ->assertInertia(fn ($page) => $page
            ->has('inquiries.data', 1));
});

test('the queue can be narrowed by status and by kind', function () {
    Inquiry::factory()->create(['status' => InquiryStatus::Closed, 'type' => InquiryType::Booking]);
    Inquiry::factory()->create(['status' => InquiryStatus::New, 'type' => InquiryType::Feedback]);

    ($this->queue)(['status' => InquiryStatus::Closed->value])
        ->assertInertia(fn ($page) => $page
            ->where('inquiries.data', fn ($rows): bool => collect($rows)
                ->every(fn (array $row): bool => $row['status'] === InquiryStatus::Closed->value)));

    ($this->queue)(['type' => InquiryType::Feedback->value])
        ->assertInertia(fn ($page) => $page
            ->where('inquiries.data', fn ($rows): bool => collect($rows)
                ->every(fn (array $row): bool => $row['type_label'] === InquiryType::Feedback->label())));
});

test('what nobody has dealt with is listed first', function () {
    // Closed enquiries dated a day later than the one below, so recency alone
    // would put them at the top of the queue.
    Inquiry::factory()->count(2)->create([
        'status' => InquiryStatus::Closed,
        'created_at' => Carbon::now()->addDay(),
    ]);

    $newest = Inquiry::factory()->create([
        'status' => InquiryStatus::New,
        'created_at' => Carbon::now()->addMinutes(5),
    ]);

    ($this->queue)()
        ->assertInertia(fn ($page) => $page
            ->where('inquiries.data.0.id', $newest->getKey())
            ->where('inquiries.data.0.status', InquiryStatus::New->value));
});

test('one enquiry carries what was asked, and whether it can still be acted on', function () {
    ($this->open)()
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/inquiries/show')
            ->where('inquiry.subject', 'Two rooms in April')
            ->where('inquiry.email', 'chikondi.phiri@example.com')
            ->where('inquiry.phone', '+265999123456')
            ->where('inquiry.type_label', InquiryType::Booking->label())
            ->where('inquiry.status_label', InquiryStatus::New->label())
            ->where('inquiry.actionable', true)
            ->where('inquiry.assigned_name', null)
            ->where('inquiry.response', null));
});

test('an enquiry that is not on file is a 404', function () {
    $this->actingAs($this->manager)
        ->get(route('admin.inquiries.show', 999_999))
        ->assertNotFound();
});

test('picking an enquiry up gives it an owner and stops it reading as new', function () {
    $this->actingAs($this->reception)
        ->post(route('admin.inquiries.claim', $this->inquiry->getKey()))
        ->assertRedirect();

    $inquiry = $this->inquiry->refresh();

    expect($inquiry->assigned_to)->toBe($this->reception->getKey())
        ->and($inquiry->status)->toBe(InquiryStatus::InProgress);
});

test('picking up somebody else\'s enquiry says whose it was', function () {
    $this->inquiry->forceFill(['assigned_to' => $this->reception->getKey()])->save();

    $this->actingAs($this->manager)
        ->post(route('admin.inquiries.claim', $this->inquiry->getKey()))
        ->assertSessionHas('inertia.flash_data.toast.message', "Picked up from {$this->reception->name}.");

    expect($this->inquiry->refresh()->assigned_to)->toBe($this->manager->getKey());
});

test('a reply is recorded with the time it went out', function () {
    $this->travelTo('2026-09-24 09:30:00');

    $this->actingAs($this->manager)
        ->post(route('admin.inquiries.respond', $this->inquiry->getKey()), [
            'response' => '  Yes - two doubles are free that weekend. Shall I hold them?  ',
        ])
        ->assertRedirect();

    $inquiry = $this->inquiry->refresh();

    expect($inquiry->response)->toBe('Yes - two doubles are free that weekend. Shall I hold them?')
        ->and($inquiry->responded_at?->toDateTimeString())->toBe('2026-09-24 09:30:00')
        ->and($inquiry->status)->toBe(InquiryStatus::Responded)
        // Answering something is taking it on, so the reply gives it an owner too.
        ->and($inquiry->assigned_to)->toBe($this->manager->getKey());
});

test('an enquiry cannot be filed as responded with nothing written on it', function () {
    $this->actingAs($this->manager)
        ->patch(route('admin.inquiries.status', $this->inquiry->getKey()), [
            'status' => InquiryStatus::Responded->value,
        ])
        ->assertSessionHasErrors('status');

    expect($this->inquiry->refresh()->status)->toBe(InquiryStatus::New);
});

test('closing an enquiry and filing it as spam are different things', function () {
    $this->actingAs($this->manager)
        ->patch(route('admin.inquiries.status', $this->inquiry->getKey()), [
            'status' => InquiryStatus::Closed->value,
        ])
        ->assertSessionHas('inertia.flash_data.toast.message', 'Enquiry closed.');

    expect($this->inquiry->refresh()->status)->toBe(InquiryStatus::Closed);

    $other = Inquiry::factory()->create(['status' => InquiryStatus::New]);

    $this->actingAs($this->manager)
        ->patch(route('admin.inquiries.status', $other->getKey()), [
            'status' => InquiryStatus::Spam->value,
        ])
        ->assertSessionHas('inertia.flash_data.toast.message', 'Enquiry filed as spam.');

    expect($other->refresh()->status)->toBe(InquiryStatus::Spam);
});

test('a closed enquiry cannot be answered, and says so', function () {
    $this->inquiry->forceFill(['status' => InquiryStatus::Closed])->save();

    $this->actingAs($this->manager)
        ->post(route('admin.inquiries.respond', $this->inquiry->getKey()), [
            'response' => 'Trying to answer something already closed.',
        ])
        ->assertSessionHas('inertia.flash_data.toast.type', 'error')
        ->assertSessionHas(
            'inertia.flash_data.toast.message',
            'This enquiry is closed. Reopen it first if the guest has written again.',
        );

    expect($this->inquiry->refresh()->response)->toBeNull();
});

test('an enquiry filed as spam cannot be picked up', function () {
    $this->inquiry->forceFill(['status' => InquiryStatus::Spam])->save();

    $this->actingAs($this->manager)
        ->post(route('admin.inquiries.claim', $this->inquiry->getKey()))
        ->assertSessionHas('inertia.flash_data.toast.type', 'error')
        ->assertSessionHas(
            'inertia.flash_data.toast.message',
            'This enquiry is filed as spam. Reopen it first if it is a real one.',
        );

    expect($this->inquiry->refresh()->assigned_to)->toBeNull();
});

test('reopening puts an enquiry back where it was', function () {
    $this->inquiry->forceFill([
        'status' => InquiryStatus::Closed,
        'assigned_to' => $this->reception->getKey(),
    ])->save();

    // Somebody owns it, so it goes back to in progress rather than into the
    // queue as though nobody had ever seen it.
    ($this->open)() // the page's own view of it
        ->assertInertia(fn ($page) => $page->where('inquiry.actionable', false));

    $this->actingAs($this->manager)
        ->patch(route('admin.inquiries.status', $this->inquiry->getKey()), [
            'status' => InquiryStatus::InProgress->value,
        ])
        ->assertSessionHas('inertia.flash_data.toast.message', 'Enquiry reopened.');

    expect($this->inquiry->refresh()->status)->toBe(InquiryStatus::InProgress);
});

test('an enquiry that is not real is still a record of what was filed', function () {
    // Filing as spam keeps the message: it is evidence, and the hotel's counts of
    // what came in depend on it being there.
    $this->actingAs($this->manager)
        ->patch(route('admin.inquiries.status', $this->inquiry->getKey()), [
            'status' => InquiryStatus::Spam->value,
        ])
        ->assertRedirect();

    $inquiry = $this->inquiry->refresh();

    expect($inquiry->status)->toBe(InquiryStatus::Spam)
        ->and($inquiry->message)->toBe('Do you have two doubles for the first weekend of April?');
});
