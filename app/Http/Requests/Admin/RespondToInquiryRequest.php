<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The reply the hotel wrote back.
 *
 * The text is stored on the enquiry rather than sent from here: this records what
 * was said and when, so the next person to open it can see the answer without
 * having to search a mailbox. Sending it is the mail client's business.
 */
final class RespondToInquiryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Two characters, so a stray keystroke is not stored as a reply.
            'response' => ['required', 'string', 'min:2', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'response.required' => 'Write the reply before recording it.',
            'response.min' => 'That reply is too short to have been sent.',
        ];
    }

    public function response(): string
    {
        return trim((string) $this->input('response'));
    }
}
