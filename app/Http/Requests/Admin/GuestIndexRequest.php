<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The filters the guest list accepts.
 *
 * Everything is optional, so the desk can open the list and narrow it down rather
 * than having to know what they are looking for before they see anything.
 */
final class GuestIndexRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'returning' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['recent', 'spend'])],
        ];
    }

    public function search(): string
    {
        return $this->string('search')->trim()->value();
    }

    public function returningOnly(): bool
    {
        return $this->boolean('returning');
    }

    /**
     * What the list is ordered by. Recent is the default because the desk is
     * usually looking for somebody who has just been in touch; by spend is what
     * marketing reaches for.
     */
    public function sort(): string
    {
        return $this->string('sort')->trim()->value() === 'spend' ? 'spend' : 'recent';
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
            'returning' => $this->returningOnly() ? '1' : '',
            'sort' => $this->sort(),
        ];
    }
}
