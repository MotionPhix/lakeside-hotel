<?php

namespace App\Http\Requests\Admin;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The filters the reservations list accepts.
 *
 * Everything is optional, so the desk can open the list and narrow it down rather
 * than having to compose a query before seeing anything.
 */
final class BookingIndexRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'unpaid' => ['nullable', 'boolean'],
        ];
    }

    public function search(): string
    {
        return $this->string('search')->trim()->value();
    }

    public function status(): ?BookingStatus
    {
        return BookingStatus::tryFrom($this->string('status')->trim()->value());
    }

    public function from(): ?string
    {
        return $this->filled('from') ? (string) $this->input('from') : null;
    }

    public function to(): ?string
    {
        return $this->filled('to') ? (string) $this->input('to') : null;
    }

    public function unpaidOnly(): bool
    {
        return $this->boolean('unpaid');
    }

    /**
     * The filters as the frontend should hold them, so the form stays filled in.
     *
     * @return array<string, string>
     */
    public function summary(): array
    {
        return [
            'search' => $this->search(),
            'status' => $this->status()?->value ?? '',
            'from' => $this->from() ?? '',
            'to' => $this->to() ?? '',
            'unpaid' => $this->unpaidOnly() ? '1' : '',
        ];
    }
}
