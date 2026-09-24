<?php

namespace App\Http\Requests\Admin;

use App\Enums\InquiryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filing an enquiry: closed, spam, or opened back up.
 *
 * Marking one responded is refused here rather than allowed and then contradicted,
 * because the record is meant to show what was said. The reply is what makes an
 * enquiry responded, so it is recorded on the reply form and this follows it.
 */
final class UpdateInquiryStatusRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(InquiryStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Choose what to file this enquiry as.',
            'status.enum' => 'That is not one of the ways an enquiry can be filed.',
        ];
    }

    public function status(): InquiryStatus
    {
        return InquiryStatus::from((string) $this->input('status'));
    }
}
