<?php

namespace App\Models;

use Database\Factories\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Somebody who asked to hear about offers and events.
 *
 * @property int $id
 * @property string $email
 * @property string|null $name
 * @property string $status
 * @property string|null $source
 * @property Carbon|null $subscribed_at
 * @property Carbon|null $unsubscribed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['email', 'name', 'status', 'source', 'subscribed_at', 'unsubscribed_at'])]
class NewsletterSubscriber extends Model
{
    /** @use HasFactory<NewsletterSubscriberFactory> */
    use HasFactory;

    public const STATUS_SUBSCRIBED = 'subscribed';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    public const STATUS_BOUNCED = 'bounced';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    /**
     * Add an address to the list, or bring it back if it had left.
     */
    public static function subscribe(string $email, ?string $name = null, ?string $source = null): self
    {
        $subscriber = static::query()->firstOrNew(['email' => mb_strtolower($email)]);

        $subscriber->fill([
            'name' => $name ?? $subscriber->name,
            'source' => $source ?? $subscriber->source,
            'status' => self::STATUS_SUBSCRIBED,
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ])->save();

        return $subscriber;
    }

    /**
     * Whether this address is currently on the list.
     */
    public function isSubscribed(): bool
    {
        return $this->status === self::STATUS_SUBSCRIBED;
    }

    /**
     * Take the address off the list.
     */
    public function unsubscribe(): void
    {
        $this->forceFill([
            'status' => self::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now(),
        ])->save();
    }

    /**
     * Limit the query to addresses currently on the list.
     *
     * @param  Builder<NewsletterSubscriber>  $query
     */
    #[Scope]
    protected function subscribed(Builder $query): void
    {
        $query->where('status', self::STATUS_SUBSCRIBED);
    }
}
