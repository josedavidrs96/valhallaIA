<?php

declare(strict_types=1);

namespace Tests\Unit\Billing\Payment;

use App\Src\Billing\Payment\Domain\Entities\Payment;
use App\Src\Billing\Payment\Domain\Exceptions\InvalidPaymentAmountException;
use App\Src\Billing\Payment\Domain\ValueObjects\PaymentId;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Core\Member\Domain\ValueObjects\MembershipPlanId;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use PHPUnit\Framework\TestCase;

final class PaymentTest extends TestCase
{
    private function makePayment(int $amountCents = 3000, string $date = '2026-06-01'): Payment
    {
        return Payment::create(
            PaymentId::random(),
            new MemberId(),
            new MembershipPlanId(),
            new UserId(),
            $amountCents,
            new \DateTimeImmutable($date),
        );
    }

    public function test_create_derives_billing_month_from_date(): void
    {
        $p = $this->makePayment(date: '2026-06-05');
        $this->assertSame('2026-06', $p->billingMonth);
    }

    public function test_create_throws_for_zero_amount(): void
    {
        $this->expectException(InvalidPaymentAmountException::class);
        $this->makePayment(amountCents: 0);
    }

    public function test_create_throws_for_negative_amount(): void
    {
        $this->expectException(InvalidPaymentAmountException::class);
        $this->makePayment(amountCents: -100);
    }

    public function test_create_sets_properties(): void
    {
        $p = $this->makePayment(amountCents: 5000, date: '2026-03-15');
        $this->assertSame(5000, $p->amountCents);
        $this->assertSame('2026-03', $p->billingMonth);
        $this->assertSame('2026-03-15', $p->paymentDate->format('Y-m-d'));
    }
}
