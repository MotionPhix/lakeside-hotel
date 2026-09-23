<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when PayChangu cannot be reached, refuses a request, or answers with
 * something we do not understand. Never caught and swallowed: a booking that
 * cannot be paid for has to be told about, not left half done.
 */
final class PaymentGatewayException extends RuntimeException
{
    public static function rejected(string $action, int $status, string $body): self
    {
        return new self("PayChangu refused the {$action} request (HTTP {$status}): ".mb_substr($body, 0, 300));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function unexpected(string $action, array $payload): self
    {
        return new self(sprintf(
            'PayChangu answered the %s request without the fields we need: %s',
            $action,
            mb_substr((string) json_encode($payload), 0, 300),
        ));
    }

    public static function notConfigured(): self
    {
        return new self('PayChangu has no secret key configured, so online payment is unavailable.');
    }
}
