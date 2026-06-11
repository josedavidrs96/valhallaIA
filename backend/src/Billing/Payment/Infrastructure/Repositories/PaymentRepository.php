<?php

declare(strict_types=1);

namespace App\Src\Billing\Payment\Infrastructure\Repositories;

use App\Src\Billing\Payment\Domain\Entities\Payment;
use App\Src\Billing\Payment\Domain\Exceptions\PaymentNotFoundException;
use App\Src\Billing\Payment\Domain\ReadModels\MemberPaymentListItemRM;
use App\Src\Billing\Payment\Domain\ReadModels\OverdueMemberRM;
use App\Src\Billing\Payment\Domain\ReadModels\PaymentDetailRM;
use App\Src\Billing\Payment\Domain\ReadModels\PaymentListItemRM;
use App\Src\Billing\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Src\Billing\Payment\Domain\ValueObjects\PaymentId;
use App\Src\Billing\Payment\Infrastructure\Hydrators\PaymentHydrator;
use App\Src\Billing\Payment\Infrastructure\Persistence\PaymentModel;
use App\Src\Billing\Payment\Infrastructure\Tables\PaymentTable;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use Illuminate\Support\Facades\DB;

final class PaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(private readonly PaymentHydrator $hydrator) {}

    public function save(Payment $payment): void
    {
        PaymentModel::create($this->hydrator->dehydrate($payment));
    }

    public function getById(PaymentId $id): Payment
    {
        $model = PaymentModel::query()->find($id->value());

        if ($model === null) {
            throw new PaymentNotFoundException($id->value());
        }

        return $this->hydrator->hydrate($model);
    }

    public function getDetailById(PaymentId $id): PaymentDetailRM
    {
        $row = DB::table('payments as p')
            ->join('members as m', 'm.id', '=', 'p.member_id')
            ->join('membership_plans as mp', 'mp.id', '=', 'p.membership_plan_id')
            ->select(
                'p.id',
                'p.member_id',
                'p.membership_plan_id',
                'p.recorded_by',
                'p.amount_cents',
                'p.payment_date',
                'p.billing_month',
                'p.notes',
                'p.created_at',
                'm.member_number',
                'm.first_name',
                'm.last_name',
                'mp.name as plan_name'
            )
            ->where('p.id', $id->value())
            ->first();

        if ($row === null) {
            throw new PaymentNotFoundException($id->value());
        }

        return new PaymentDetailRM(
            id:               $row->id,
            memberId:         $row->member_id,
            memberNumber:     (int) $row->member_number,
            memberFirstName:  $row->first_name,
            memberLastName:   $row->last_name,
            membershipPlanId: $row->membership_plan_id,
            planName:         $row->plan_name,
            recordedBy:       $row->recorded_by,
            amountCents:      (int) $row->amount_cents,
            paymentDate:      (string) $row->payment_date,
            billingMonth:     $row->billing_month,
            notes:            $row->notes ?? null,
            createdAt:        $row->created_at ?? null,
        );
    }

    public function findByMemberAndBillingMonth(MemberId $memberId, string $billingMonth): ?Payment
    {
        $model = PaymentModel::query()
            ->where(PaymentTable::MEMBER_ID, $memberId->value())
            ->where(PaymentTable::BILLING_MONTH, $billingMonth)
            ->first();

        return $model ? $this->hydrator->hydrate($model) : null;
    }

    public function findAll(?string $memberId, ?int $year, ?int $month, int $page, int $perPage): array
    {
        $query = DB::table('payments as p')
            ->join('members as m', 'm.id', '=', 'p.member_id')
            ->join('membership_plans as mp', 'mp.id', '=', 'p.membership_plan_id')
            ->select(
                'p.id',
                'p.amount_cents',
                'p.payment_date',
                'p.billing_month',
                'p.created_at',
                'm.member_number',
                'm.first_name',
                'm.last_name',
                'mp.name as plan_name'
            );

        if ($memberId !== null) {
            $query->where('p.member_id', $memberId);
        }

        if ($year !== null) {
            $query->whereRaw("strftime('%Y', p.payment_date) = ?", [(string) $year]);
        }

        if ($month !== null) {
            $query->whereRaw("strftime('%m', p.payment_date) = ?", [str_pad((string) $month, 2, '0', STR_PAD_LEFT)]);
        }

        $rows = $query->orderBy('p.payment_date', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return $rows->map(fn($row) => new PaymentListItemRM(
            id:              $row->id,
            memberNumber:    (int) $row->member_number,
            memberFirstName: $row->first_name,
            memberLastName:  $row->last_name,
            amountCents:     (int) $row->amount_cents,
            paymentDate:     (string) $row->payment_date,
            billingMonth:    $row->billing_month,
            planName:        $row->plan_name,
            createdAt:       $row->created_at ?? null,
        ))->all();
    }

    public function countAll(?string $memberId, ?int $year, ?int $month): int
    {
        $query = DB::table('payments as p');

        if ($memberId !== null) {
            $query->where('p.member_id', $memberId);
        }

        if ($year !== null) {
            $query->whereRaw("strftime('%Y', p.payment_date) = ?", [(string) $year]);
        }

        if ($month !== null) {
            $query->whereRaw("strftime('%m', p.payment_date) = ?", [str_pad((string) $month, 2, '0', STR_PAD_LEFT)]);
        }

        return $query->count();
    }

    public function findOverdueMembers(string $billingMonth): array
    {
        // Cross-BC query: reads members + users tables
        // SQLite-compatible: no MySQL-specific functions
        $sql = "
            SELECT m.id, m.member_number, m.first_name, m.last_name, u.email,
                   mp.name as plan_name,
                   MAX(p_last.payment_date) as last_payment_date
            FROM members m
            JOIN users u ON u.id = m.user_id AND u.status = 'active'
            LEFT JOIN member_plan_assignments mpa
                ON mpa.member_id = m.id
                AND mpa.assigned_at = (
                    SELECT MAX(mpa2.assigned_at) FROM member_plan_assignments mpa2
                    WHERE mpa2.member_id = m.id
                )
            LEFT JOIN membership_plans mp ON mp.id = mpa.membership_plan_id
            LEFT JOIN payments p_last ON p_last.member_id = m.id
            WHERE m.deleted_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM payments p
                  WHERE p.member_id = m.id AND p.billing_month = ?
              )
            GROUP BY m.id, m.member_number, m.first_name, m.last_name, u.email, mp.name
            ORDER BY m.member_number ASC
        ";

        $rows = DB::select($sql, [$billingMonth]);

        return array_map(fn($row) => new OverdueMemberRM(
            memberId:        $row->id,
            memberNumber:    (int) $row->member_number,
            firstName:       $row->first_name,
            lastName:        $row->last_name,
            email:           $row->email,
            planName:        $row->plan_name ?? null,
            lastPaymentDate: $row->last_payment_date ?? null,
        ), $rows);
    }

    public function findByMemberId(MemberId $memberId): array
    {
        $rows = DB::table('payments as p')
            ->join('membership_plans as mp', 'mp.id', '=', 'p.membership_plan_id')
            ->select('p.id', 'p.amount_cents', 'p.payment_date', 'p.billing_month', 'mp.name as plan_name')
            ->where('p.member_id', $memberId->value())
            ->orderBy('p.payment_date', 'desc')
            ->get();

        return $rows->map(fn($row) => new MemberPaymentListItemRM(
            id:           $row->id,
            amountCents:  (int) $row->amount_cents,
            paymentDate:  (string) $row->payment_date,
            billingMonth: $row->billing_month,
            planName:     $row->plan_name,
        ))->all();
    }
}
