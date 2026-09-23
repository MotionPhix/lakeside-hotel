<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

enum InquiryType: string
{
    use ProvidesOptions;

    case General = 'general';
    case Booking = 'booking';
    case Conference = 'conference';
    case Wedding = 'wedding';
    case Event = 'event';
    case Group = 'group';
    case Feedback = 'feedback';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General enquiry',
            self::Booking => 'Booking enquiry',
            self::Conference => 'Conference',
            self::Wedding => 'Wedding',
            self::Event => 'Private event',
            self::Group => 'Group booking',
            self::Feedback => 'Feedback',
        };
    }
}
