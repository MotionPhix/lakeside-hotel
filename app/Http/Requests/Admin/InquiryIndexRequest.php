<?php

namespace App\Http\Requests\Admin;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The filters the enquiry list accepts.
 *
 * Everything is optional: the desk opens the list and narrows it, rather than
 * having to know what they want before anything is on screen.
 */
final class InquiryIndexRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::enum(InquiryStatus::class)],
            'type' => ['nullable', Rule::enum(InquiryType::class)],
        ];
    }

    public function search(): string
    {
        return $this->string('search')->trim()->value();
    }

    public function status(): ?InquiryStatus
    {
        $status = $this->string('status')->trim()->value();

        return $status === '' ? null : InquiryStatus::tryFrom($status);
    }

    public function type(): ?InquiryType
    {
        $type = $this->string('type')->trim()->value();

        return $type === '' ? null : InquiryType::tryFrom($type);
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
            'type' => $this->type()?->value ?? '',
        ];
    }
}
