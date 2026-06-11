<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Infrastructure\Persistence;

use App\Src\Billing\Payment\Infrastructure\Tables\PaymentTable;
use Illuminate\Database\Eloquent\Model;

final class PaymentModel extends Model
{
    protected $table = PaymentTable::TABLE_NAME;

    protected $primaryKey = PaymentTable::ID;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        PaymentTable::ID,
        PaymentTable::MEMBER_ID,
        PaymentTable::MEMBERSHIP_PLAN_ID,
        PaymentTable::RECORDED_BY,
        PaymentTable::AMOUNT_CENTS,
        PaymentTable::PAYMENT_DATE,
        PaymentTable::BILLING_MONTH,
        PaymentTable::NOTES,
    ];

    protected function casts(): array
    {
        return [
            PaymentTable::PAYMENT_DATE => 'date',
        ];
    }
}
