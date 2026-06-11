<?php

declare(strict_types=1);

namespace App\Http\Actions\Payments\Record;

use Illuminate\Foundation\Http\FormRequest;

final class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getDto(): RecordPaymentDto
    {
        return new RecordPaymentDto(
            memberId:         (string) $this->input('member_id'),
            membershipPlanId: (string) $this->input('membership_plan_id'),
            amountCents:      (int) $this->input('amount_cents', 0),
            paymentDate:      new \DateTimeImmutable((string) $this->input('payment_date', 'today')),
            notes:            $this->input('notes') ? (string) $this->input('notes') : null,
        );
    }
}
