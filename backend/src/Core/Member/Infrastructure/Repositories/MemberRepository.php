<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Infrastructure\Repositories;

use App\Src\Core\Member\Domain\Entities\Member;
use App\Src\Core\Member\Domain\Exceptions\MemberNotFoundException;
use App\Src\Core\Member\Domain\ReadModels\MemberDetailRM;
use App\Src\Core\Member\Domain\ReadModels\MemberListItemRM;
use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Core\Member\Infrastructure\Hydrators\MemberHydrator;
use App\Src\Core\Member\Infrastructure\Persistence\MemberModel;
use App\Src\Core\Member\Infrastructure\Tables\MemberPlanAssignmentTable;
use App\Src\Core\Member\Infrastructure\Tables\MembershipPlanTable;
use App\Src\Core\Member\Infrastructure\Tables\MemberTable;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Support\Facades\DB;

final class MemberRepository implements MemberRepositoryInterface
{
    public function __construct(private readonly MemberHydrator $hydrator) {}

    public function getById(MemberId $id): Member
    {
        $model = MemberModel::query()
            ->where(MemberTable::ID, $id->value())
            ->first();

        if ($model === null) {
            throw new MemberNotFoundException("Member with id '{$id->value()}' not found");
        }

        return $this->hydrator->hydrate($model);
    }

    public function findByUserId(UserId $userId): ?Member
    {
        $model = MemberModel::query()
            ->where(MemberTable::USER_ID, $userId->value())
            ->first();

        return $model ? $this->hydrator->hydrate($model) : null;
    }

    public function save(Member $member): void
    {
        MemberModel::query()->updateOrCreate(
            [MemberTable::ID => $member->id->value()],
            $this->hydrator->dehydrate($member),
        );
    }

    public function nextMemberNumber(): int
    {
        // lockForUpdate() requires an active transaction — the caller must ensure one exists
        $max = DB::table(MemberTable::TABLE_NAME)
            ->lockForUpdate()
            ->max(MemberTable::MEMBER_NUMBER);

        return ($max ?? 0) + 1;
    }

    /**
     * @return MemberListItemRM[]
     */
    public function findAll(?string $status, ?string $planId, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $rows = DB::select(
            $this->buildListQuery() . ' ORDER BY m.' . MemberTable::MEMBER_NUMBER . ' ASC LIMIT ? OFFSET ?',
            $this->buildListBindings($status, $planId, $perPage, $offset)
        );

        return array_map(fn(object $row) => new MemberListItemRM(
            id:           $row->id,
            memberNumber: (int) $row->member_number,
            firstName:    $row->first_name,
            lastName:     $row->last_name,
            email:        $row->email,
            status:       $row->status,
            planName:     $row->plan_name ?? null,
            planId:       $row->plan_id ?? null,
            joinDate:     $row->join_date,
        ), $rows);
    }

    public function countAll(?string $status, ?string $planId): int
    {
        $rows = DB::select(
            'SELECT COUNT(*) as cnt FROM (' . $this->buildListQuery() . ') AS sub',
            $this->buildCountBindings($status, $planId)
        );

        return (int) ($rows[0]->cnt ?? 0);
    }

    public function getDetailById(MemberId $id): MemberDetailRM
    {
        $row = $this->queryDetail('m.' . MemberTable::ID . ' = ?', [$id->value()]);

        if ($row === null) {
            throw new MemberNotFoundException("Member with id '{$id->value()}' not found");
        }

        return $this->rowToDetailRM($row);
    }

    public function getDetailByUserId(UserId $userId): MemberDetailRM
    {
        $row = $this->queryDetail('m.' . MemberTable::USER_ID . ' = ?', [$userId->value()]);

        if ($row === null) {
            throw new MemberNotFoundException("Member with user_id '{$userId->value()}' not found");
        }

        return $this->rowToDetailRM($row);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function buildListQuery(): string
    {
        return '
            SELECT
                m.id,
                m.member_number,
                m.first_name,
                m.last_name,
                m.join_date,
                u.email,
                u.status,
                mp.id   AS plan_id,
                mp.name AS plan_name
            FROM members m
            JOIN users u ON u.id = m.user_id
            LEFT JOIN member_plan_assignments mpa
                ON mpa.member_id = m.id
                AND mpa.assigned_at = (
                    SELECT MAX(assigned_at)
                    FROM member_plan_assignments
                    WHERE member_id = m.id
                )
            LEFT JOIN membership_plans mp ON mp.id = mpa.membership_plan_id
            WHERE m.deleted_at IS NULL
              AND (? IS NULL OR u.status = ?)
              AND (? IS NULL OR mpa.membership_plan_id = ?)
        ';
    }

    private function buildListBindings(?string $status, ?string $planId, int $perPage, int $offset): array
    {
        return [$status, $status, $planId, $planId, $perPage, $offset];
    }

    private function buildCountBindings(?string $status, ?string $planId): array
    {
        return [$status, $status, $planId, $planId];
    }

    private function queryDetail(string $whereClause, array $bindings): ?object
    {
        $sql = '
            SELECT
                m.id,
                m.user_id,
                m.member_number,
                m.first_name,
                m.last_name,
                m.phone,
                m.date_of_birth,
                m.profile_photo,
                m.join_date,
                m.emergency_contact_name,
                m.emergency_contact_phone,
                m.notes,
                m.created_at,
                u.email,
                u.status,
                mp.id              AS plan_id,
                mp.name            AS plan_name,
                mp.price_cents     AS plan_price_cents,
                mp.classes_per_month AS plan_classes_per_month
            FROM members m
            JOIN users u ON u.id = m.user_id
            LEFT JOIN member_plan_assignments mpa
                ON mpa.member_id = m.id
                AND mpa.assigned_at = (
                    SELECT MAX(assigned_at)
                    FROM member_plan_assignments
                    WHERE member_id = m.id
                )
            LEFT JOIN membership_plans mp ON mp.id = mpa.membership_plan_id
            WHERE m.deleted_at IS NULL
              AND ' . $whereClause . '
            LIMIT 1
        ';

        $rows = DB::select($sql, $bindings);

        return $rows[0] ?? null;
    }

    private function rowToDetailRM(object $row): MemberDetailRM
    {
        return new MemberDetailRM(
            id:                   $row->id,
            userId:               $row->user_id,
            memberNumber:         (int) $row->member_number,
            firstName:            $row->first_name,
            lastName:             $row->last_name,
            email:                $row->email,
            phone:                $row->phone ?? null,
            dateOfBirth:          $row->date_of_birth ?? null,
            profilePhoto:         $row->profile_photo ?? null,
            joinDate:             $row->join_date,
            status:               $row->status,
            planId:               $row->plan_id ?? null,
            planName:             $row->plan_name ?? null,
            planPriceCents:       isset($row->plan_price_cents) ? (int) $row->plan_price_cents : null,
            planClassesPerMonth:  isset($row->plan_classes_per_month) ? (int) $row->plan_classes_per_month : null,
            createdAt:            $row->created_at,
            emergencyContactName:  $row->emergency_contact_name ?? null,
            emergencyContactPhone: $row->emergency_contact_phone ?? null,
            notes:                $row->notes ?? null,
        );
    }
}
