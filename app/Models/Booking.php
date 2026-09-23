<?php

namespace App\Models;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\PaymentOption;
use App\Enums\PaymentStatus;
use Carbon\CarbonInterface;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A reservation: who is coming, for which nights, in which room categories, and
 * how it has been paid for.
 *
 * Money is stored in the hotel's trading currency (MWK) and broken down into
 * subtotal, discount, tax and total so a folio can always be reconstructed.
 *
 * @property int $id
 * @property string $reference
 * @property int $guest_id
 * @property BookingStatus $status
 * @property BookingSource $source
 * @property Carbon $check_in
 * @property Carbon $check_out
 * @property int $nights
 * @property int $adults
 * @property int $children
 * @property string $currency
 * @property string $subtotal
 * @property string $discount_total
 * @property string $tax_total
 * @property string $total
 * @property string $amount_paid
 * @property PaymentStatus $payment_status
 * @property PaymentOption|null $payment_method
 * @property int|null $coupon_id
 * @property string|null $special_requests
 * @property bool $airport_transfer
 * @property array<string, mixed>|null $transfer_details
 * @property string|null $internal_notes
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $checked_in_at
 * @property Carbon|null $checked_out_at
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'reference', 'guest_id', 'status', 'source', 'check_in', 'check_out', 'nights',
    'adults', 'children', 'currency', 'subtotal', 'discount_total', 'tax_total', 'total',
    'amount_paid', 'payment_status', 'payment_method', 'coupon_id', 'special_requests',
    'airport_transfer', 'transfer_details', 'internal_notes', 'confirmed_at',
    'checked_in_at', 'checked_out_at', 'cancelled_at', 'cancellation_reason', 'created_by',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * Value added tax charged on accommodation in Malawi.
     */
    public const VAT_RATE = 16.5;

    /**
     * Tourism levy charged per night on accommodation in Malawi.
     */
    public const TOURISM_LEVY_RATE = 1.0;

    /**
     * The currency the hotel trades in, used when nothing has been configured
     * yet. The live value is the `hotel.currency` setting, which the dashboard
     * owns.
     */
    public const CURRENCY = 'MWK';

    /**
     * The currency bookings are denominated in.
     */
    public static function currency(): string
    {
        return (string) Setting::value('hotel.currency', self::CURRENCY);
    }

    /**
     * The combined accommodation tax rate as a percentage. Both parts are
     * editable in the dashboard, with the statutory figures above as the fallback.
     */
    public static function taxRate(): float
    {
        return (float) Setting::value('booking.vat_rate', (string) self::VAT_RATE)
            + (float) Setting::value('booking.tourism_levy_rate', (string) self::TOURISM_LEVY_RATE);
    }

    /**
     * Tax on an amount. Stated once so a quote shown to a guest and the folio they
     * are finally billed on cannot disagree.
     */
    public static function taxFor(string $taxable): string
    {
        return number_format((float) $taxable * (self::taxRate() / 100), 2, '.', '');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'source' => BookingSource::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentOption::class,
            'check_in' => 'date',
            'check_out' => 'date',
            'nights' => 'integer',
            'adults' => 'integer',
            'children' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'airport_transfer' => 'boolean',
            'transfer_details' => 'array',
            'confirmed_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * The guest who booked.
     *
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * The room lines that make up the stay.
     *
     * @return HasMany<BookingItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    /**
     * Every payment attempt against this booking.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    /**
     * The discount code applied at checkout, if any.
     *
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * The staff member who took the booking.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * How many guests the booking covers in total.
     */
    public function totalGuests(): int
    {
        return $this->adults + $this->children;
    }

    /**
     * What the guest still owes.
     */
    public function balance(): string
    {
        return number_format(max((float) $this->total - (float) $this->amount_paid, 0), 2, '.', '');
    }

    /**
     * Whether the booking is fully settled.
     */
    public function isPaidInFull(): bool
    {
        return (float) $this->amount_paid >= (float) $this->total;
    }

    /**
     * Whether the guest is still expected to arrive.
     */
    public function isUpcoming(): bool
    {
        return ! $this->status->isClosed() && $this->check_in->isFuture();
    }

    /**
     * Total the room lines, then apply the discount and accommodation taxes.
     *
     * Returns the model so a caller can chain `save()`.
     */
    public function recalculateTotals(): self
    {
        $subtotal = (float) $this->items()->sum('subtotal');

        $discount = $this->coupon instanceof Coupon
            ? (float) $this->coupon->discountFor(number_format($subtotal, 2, '.', ''))
            : 0.0;

        $taxable = max($subtotal - $discount, 0);
        $tax = (float) self::taxFor(number_format($taxable, 2, '.', ''));

        $this->subtotal = number_format($subtotal, 2, '.', '');
        $this->discount_total = number_format($discount, 2, '.', '');
        $this->tax_total = number_format($tax, 2, '.', '');
        $this->total = number_format($taxable + $tax, 2, '.', '');

        return $this;
    }

    /**
     * Refresh the payment status from the settled payments on the booking.
     */
    public function syncPaymentStatus(): self
    {
        $paid = (float) $this->payments()->where('status', 'successful')->sum('amount');
        $refunded = (float) $this->payments()->where('status', 'refunded')->sum('refunded_amount');

        $this->amount_paid = number_format(max($paid - $refunded, 0), 2, '.', '');

        $total = (float) $this->total;

        $this->payment_status = match (true) {
            $refunded > 0 && $refunded >= $paid => PaymentStatus::Refunded,
            $refunded > 0 => PaymentStatus::PartiallyRefunded,
            $total > 0 && (float) $this->amount_paid >= $total => PaymentStatus::Paid,
            (float) $this->amount_paid > 0 => PaymentStatus::DepositPaid,
            default => PaymentStatus::Unpaid,
        };

        return $this;
    }

    /**
     * Build the next human-friendly booking reference, e.g. `LH-2026-0007`.
     */
    public static function generateReference(): string
    {
        $prefix = 'LH-'.now()->format('Y').'-';

        $latest = static::query()
            ->where('reference', 'like', $prefix.'%')
            ->orderByDesc('reference')
            ->value('reference');

        $next = $latest === null
            ? 1
            : ((int) substr((string) $latest, strlen($prefix))) + 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Limit the query to bookings that still hold rooms for their dates.
     *
     * @param  Builder<Booking>  $query
     */
    #[Scope]
    protected function holdingInventory(Builder $query): void
    {
        $query->whereIn('status', [
            BookingStatus::Pending->value,
            BookingStatus::Confirmed->value,
            BookingStatus::CheckedIn->value,
        ]);
    }

    /**
     * Limit the query to guests arriving on a given date.
     *
     * @param  Builder<Booking>  $query
     */
    #[Scope]
    protected function arrivingOn(Builder $query, CarbonInterface $date): void
    {
        $query->whereDate('check_in', $date)
            ->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Confirmed->value]);
    }

    /**
     * Limit the query to guests checking out on a given date.
     *
     * @param  Builder<Booking>  $query
     */
    #[Scope]
    protected function departingOn(Builder $query, CarbonInterface $date): void
    {
        $query->whereDate('check_out', $date)
            ->where('status', BookingStatus::CheckedIn->value);
    }

    /**
     * Limit the query to live bookings whose stay covers a given night. Used for
     * occupancy, so cancelled and no-show bookings are excluded.
     *
     * @param  Builder<Booking>  $query
     */
    #[Scope]
    protected function coveringDate(Builder $query, CarbonInterface $date): void
    {
        $query->holdingInventory()
            ->where('check_in', '<=', $date)
            ->where('check_out', '>', $date);
    }
}
