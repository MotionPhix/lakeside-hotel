<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when the desk asks an enquiry to do something it is not in a state to
 * do: writing back to something already filed as spam, or picking up one that has
 * been closed off.
 *
 * Ordinary refusals rather than faults - somebody clicking a button twice, or on a
 * message a colleague has already dealt with - so the controller turns them into a
 * message rather than letting them become a 500.
 */
final class InquiryNotActionable extends RuntimeException
{
    public static function spam(): self
    {
        return new self('This enquiry is filed as spam. Reopen it first if it is a real one.');
    }

    public static function closed(): self
    {
        return new self('This enquiry is closed. Reopen it first if the guest has written again.');
    }
}
