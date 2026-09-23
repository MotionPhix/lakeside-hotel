<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

enum InquiryStatus: string
{
    use ProvidesOptions;

    case New = 'new';
    case InProgress = 'in_progress';
    case Responded = 'responded';
    case Closed = 'closed';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::InProgress => 'In progress',
            self::Responded => 'Responded',
            self::Closed => 'Closed',
            self::Spam => 'Spam',
        };
    }

    /**
     * Whether the enquiry still needs somebody to act on it.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::InProgress], true);
    }

    public function variant(): string
    {
        return match ($this) {
            self::New => 'default',
            self::InProgress => 'secondary',
            self::Responded => 'outline',
            self::Closed => 'outline',
            self::Spam => 'destructive',
        };
    }
}
