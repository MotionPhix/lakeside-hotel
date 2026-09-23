<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single payment attempt against a booking, made through PayChangu (card or
 * mobile money) or recorded by reception for cash and bank transfers.
 *
 * @property int $id
 * @property int $booking_id
 * @property string $provider
 * @property string|null $provider_reference
 * @property string|null $provider_transaction_id
 * @property PaymentMethod|null $method
 * @property string $amount
 * @property string $currency
 * @property PaymentRecordStatus $status
 * @property Carbon|null $paid_at
 * @property array<string, mixed>|null $payload
 * @property string $refunded_amount
 * @property Carbon|null $refunded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'booking_id', 'provider', 'provider_reference', 'provider_transaction_id', 'method',
    'amount', 'currency', 'status', 'paid_at', 'payload', 'refunded_amount', 'refunded_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * The gateway the hotel settles through.
     */
    public const PROVIDER_PAYCHANGU = 'paychangu';

    /**
     * Payments taken at the desk rather than online.
     */
    public const PROVIDER_MANUAL = 'manual';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentRecordStatus::class,
            'amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    /**
     * The booking this payment settles.
     *
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Whether the money has arrived.
     */
    public function isSuccessful(): bool
    {
        return $this->status->isSettled();
    }

    /**
     * Whether the gateway is still working on it.
     */
    public function isPending(): bool
    {
        return $this->status === PaymentRecordStatus::Pending;
    }

    /**
     * Whether this payment came in through the online gateway.
     */
    public function isOnline(): bool
    {
        return $this->provider === self::PROVIDER_PAYCHANGU;
    }

    /**
     * Record that the gateway confirmed the payment.
     *
     * @param  array<string, mixed>  $payload
     */
    public function markSuccessful(?PaymentMethod $method = null, array $payload = []): void
    {
        $this->forceFill([
            'status' => PaymentRecordStatus::Successful,
            'method' => $method ?? $this->method,
            'paid_at' => $this->paid_at ?? now(),
            'payload' => $payload === [] ? $this->payload : $payload,
        ])->save();
    }

    /**
     * Record that the attempt failed.
     *
     * @param  array<string, mixed>  $payload
     */
    public function markFailed(array $payload = []): void
    {
        $this->forceFill([
            'status' => PaymentRecordStatus::Failed,
            'payload' => $payload === [] ? $this->payload : $payload,
        ])->save();
    }

    /**
     * Record a refund against this payment.
     */
    public function markRefunded(?string $amount = null): void
    {
        $this->forceFill([
            'status' => PaymentRecordStatus::Refunded,
            'refunded_amount' => $amount ?? $this->amount,
            'refunded_at' => now(),
        ])->save();
    }

    /**
     * Limit the query to payments the hotel actually received.
     *
     * @param  Builder<Payment>  $query
     */
    public function scopeSuccessful(Builder $query): void
    {
        $query->where('status', PaymentRecordStatus::Successful);
    }
}
