<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Infrastructure\Tables;

final class PaymentTable
{
    public const TABLE_NAME         = 'payments';
    public const ID                 = 'id';
    public const MEMBER_ID          = 'member_id';
    public const MEMBERSHIP_PLAN_ID = 'membership_plan_id';
    public const RECORDED_BY        = 'recorded_by';
    public const AMOUNT_CENTS       = 'amount_cents';
    public const PAYMENT_DATE       = 'payment_date';
    public const BILLING_MONTH      = 'billing_month';
    public const NOTES              = 'notes';
    public const CREATED_AT         = 'created_at';
    public const UPDATED_AT         = 'updated_at';
}
