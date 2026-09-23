<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use Database\Factories\InquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A message from the website contact form, an event request or a group booking
 * enquiry, together with the hotel's reply.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $subject
 * @property string $message
 * @property InquiryType $type
 * @property InquiryStatus $status
 * @property Carbon|null $preferred_date
 * @property int|null $guests_count
 * @property int|null $assigned_to
 * @property Carbon|null $responded_at
 * @property string|null $response
 * @property string|null $source_page
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'email', 'phone', 'subject', 'message', 'type', 'status',
    'preferred_date', 'guests_count', 'assigned_to', 'responded_at',
    'response', 'source_page',
])]
class Inquiry extends Model
{
    /** @use HasFactory<InquiryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InquiryType::class,
            'status' => InquiryStatus::class,
            'preferred_date' => 'date',
            'guests_count' => 'integer',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * The staff member handling the enquiry.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Whether the hotel has written a reply.
     */
    public function hasResponse(): bool
    {
        return filled($this->response);
    }

    /**
     * Record the hotel's reply and close the loop.
     */
    public function markResponded(string $response, ?User $by = null): void
    {
        $this->forceFill([
            'response' => $response,
            'responded_at' => now(),
            'status' => InquiryStatus::Responded,
            'assigned_to' => $this->assigned_to ?? $by?->getKey(),
        ])->save();
    }

    /**
     * Limit the query to enquiries nobody has closed off yet.
     *
     * @param  Builder<Inquiry>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [InquiryStatus::New->value, InquiryStatus::InProgress->value]);
    }

    /**
     * Limit the query to one kind of enquiry.
     *
     * @param  Builder<Inquiry>  $query
     */
    public function scopeType(Builder $query, InquiryType $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Limit the query to enquiries matching a name, email or subject term.
     *
     * @param  Builder<Inquiry>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('subject', 'like', "%{$term}%");
        });
    }
}
