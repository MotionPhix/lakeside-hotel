<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Money taken at the desk.
 *
 * The amount is capped at what the booking actually owes, because a payment larger
 * than the balance is almost always a typo and would leave the folio overpaid with
 * no obvious way to spot it.
 */
final class RecordPaymentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $booking = $this->route('booking');
        $balance = $booking === null ? null : (float) $booking->balance();

        return [
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                $balance === null || $balance <= 0
                    ? 'max:0'
                    : 'max:'.$balance,
            ],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.max' => 'That is more than the booking still owes.',
            'amount.gt' => 'Enter an amount greater than zero.',
        ];
    }

    /**
     * The instruments the desk can take. Everything the enum knows about is
     * offered, including "other", because cash desks meet arrangements that do
     * not fit a tidy list and a forced choice would be recorded wrongly.
     *
     * @return list<array{value: string, label: string}>
     */
    public function methodOptions(): array
    {
        return PaymentMethod::options();
    }
}
