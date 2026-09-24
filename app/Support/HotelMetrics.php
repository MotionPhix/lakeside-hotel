<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\Room;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The read model behind the dashboard overview: what the front desk needs to know
 * when they open it in the morning.
 *
 * Kept apart from the controllers because the same figures are wanted in more
 * than one place - the overview today, the reporting module later - and because
 * occupancy in particular has one definition that should not be written twice.
 */
final class HotelMetrics
{
    /**
     * Everything the overview shows, gathered in one pass.
     *
     * @return array<string, mixed>
     */
    public function overview(?CarbonInterface $today = null): array
    {
        $today = ($today ?? Carbon::today())->startOfDay();

        return [
            'date' => $today->toDateString(),
            'date_label' => $today->format('l j F Y'),
            'arrivals' => $this->arrivals($today),
            'departures' => $this->departures($today),
            'in_house' => $this->inHouse(),
            'occupancy' => $this->occupancy($today),
            'revenue' => $this->revenue($today),
            'pending' => $this->pending(),
            'outstanding' => $this->outstanding(),
            'inquiries' => $this->recentInquiries(),
            'arriving_soon' => $this->arrivingSoon($today),
        ];
    }

    /**
     * Guests expected today.
     *
     * @return list<array<string, mixed>>
     */
    public function arrivals(CarbonInterface $date): array
    {
        return Booking::query()
            ->arrivingOn($date)
            ->with('guest', 'items.roomType')
            ->orderBy('check_in')
            ->get()
            ->map(fn (Booking $booking): array => $this->stay($booking))
            ->all();
    }

    /**
     * Guests leaving today, plus anybody still in house whose departure date has
     * already passed - which is the row the desk actually has to act on.
     *
     * @return list<array<string, mixed>>
     */
    public function departures(CarbonInterface $date): array
    {
        return Booking::query()
            ->where('status', BookingStatus::CheckedIn->value)
            ->whereDate('check_out', '<=', $date)
            ->with('guest', 'items.roomType')
            ->orderBy('check_out')
            ->get()
            ->map(fn (Booking $booking): array => $this->stay($booking) + [
                'overdue' => $booking->check_out->startOfDay()->lt($date),
            ])
            ->all();
    }

    /**
     * How many guests are in house right now.
     */
    public function inHouse(): int
    {
        return Booking::query()
            ->where('status', BookingStatus::CheckedIn->value)
            ->count();
    }

    /**
     * Rooms occupied tonight as a share of the rooms that can be sold.
     *
     * The numerator counts booking lines rather than allocated rooms, because a
     * reservation that has not been given a door number yet still occupies a room.
     * Rooms out of service are excluded from the denominator rather than counted
     * as free, so a maintenance block does not flatter the percentage.
     *
     * @return array{occupied: int, sellable: int, percentage: float}
     */
    public function occupancy(CarbonInterface $date): array
    {
        $sellable = Room::query()
            ->whereNotIn('status', [
                RoomStatus::Maintenance->value,
                RoomStatus::OutOfService->value,
            ])
            ->count();

        $occupied = BookingItem::query()
            ->whereHas('booking', fn ($query) => $query->coveringDate($date))
            ->count();

        $occupied = min($occupied, $sellable);

        return [
            'occupied' => $occupied,
            'sellable' => $sellable,
            'percentage' => $sellable === 0 ? 0.0 : round($occupied / $sellable * 100, 1),
        ];
    }

    /**
     * Money in, and the value of what was booked.
     *
     * `taken` is cash that has actually settled; `booked` is the value of
     * reservations taken today, which is the pipeline rather than the till.
     *
     * @return array<string, string>
     */
    public function revenue(CarbonInterface $today): array
    {
        $monthStart = $today->copy()->startOfMonth();
        $dayEnd = $today->copy()->endOfDay();

        $settled = fn (CarbonInterface $from, CarbonInterface $to): float => (float) Payment::query()
            ->where('status', PaymentRecordStatus::Successful->value)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        return [
            'taken_today' => number_format($settled($today, $dayEnd), 2, '.', ''),
            'taken_this_month' => number_format($settled($monthStart, $dayEnd), 2, '.', ''),
            'month_label' => $monthStart->format('F'),
            'booked_today' => number_format(
                (float) Booking::query()->whereDate('created_at', $today)->sum('total'),
                2,
                '.',
                '',
            ),
        ];
    }

    /**
     * Reservations waiting on the desk to confirm them.
     */
    public function pending(): int
    {
        return Booking::query()
            ->where('status', BookingStatus::Pending->value)
            ->count();
    }

    /**
     * What live bookings still owe between them.
     */
    public function outstanding(): string
    {
        $balances = Booking::query()
            ->holdingInventory()
            ->get(['total', 'amount_paid']);

        return number_format(
            max($balances->sum(fn (Booking $booking): float => (float) $booking->balance()), 0),
            2,
            '.',
            '',
        );
    }

    /**
     * The newest enquiries from the website, which nobody has answered yet unless
     * the status says otherwise.
     *
     * @return list<array<string, mixed>>
     */
    public function recentInquiries(int $limit = 5): array
    {
        return Inquiry::query()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Inquiry $inquiry): array => [
                'id' => $inquiry->id,
                'name' => $inquiry->name,
                'email' => $inquiry->email,
                'subject' => $inquiry->subject,
                'type' => $inquiry->type->label(),
                'status' => $inquiry->status->label(),
                'guests' => $inquiry->guests_count,
                'preferred_date' => $inquiry->preferred_date?->toDateString(),
                'received' => $inquiry->created_at?->diffForHumans(),
            ])
            ->all();
    }

    /**
     * How many guests are due over the coming week, day by day, so the desk can
     * see the shape of the week rather than only today.
     *
     * @return list<array<string, mixed>>
     */
    public function arrivingSoon(CarbonInterface $today, int $days = 7): array
    {
        return Booking::query()
            ->whereIn('status', [
                BookingStatus::Pending->value,
                BookingStatus::Confirmed->value,
            ])
            ->whereDate('check_in', '>', $today)
            ->whereDate('check_in', '<=', $today->copy()->addDays($days))
            ->get(['id', 'check_in', 'adults', 'children'])
            ->groupBy(fn (Booking $booking): string => $booking->check_in->toDateString())
            ->map(fn (Collection $group, string $date): array => [
                'date' => $date,
                'label' => Carbon::parse($date)->format('D j M'),
                'bookings' => $group->count(),
                'guests' => $group->sum(fn (Booking $booking): int => $booking->totalGuests()),
            ])
            ->sortKeys()
            ->values()
            ->all();
    }

    /**
     * The row shape shared by arrivals and departures.
     *
     * @return array<string, mixed>
     */
    private function stay(Booking $booking): array
    {
        return [
            'reference' => $booking->reference,
            'guest' => $booking->guest->fullName(),
            'phone' => $booking->guest->phone,
            'room' => $booking->items->first()?->roomType?->name,
            'nights' => $booking->nights,
            'guests' => $booking->totalGuests(),
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'balance' => $booking->balance(),
            'unpaid' => (float) $booking->balance() > 0,
            'airport_transfer' => $booking->airport_transfer,
        ];
    }
}
