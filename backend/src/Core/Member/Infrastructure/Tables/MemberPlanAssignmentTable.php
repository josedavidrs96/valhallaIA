<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Infrastructure\Tables;

/**
 * Constants for the member_plan_assignments table.
 *
 * @property string id                  ULID PK
 * @property string member_id           FK -> members.id
 * @property string membership_plan_id  FK -> membership_plans.id
 * @property string assigned_at         DATE — when this plan was assigned
 * @property string created_at
 * @property string updated_at
 */
final class MemberPlanAssignmentTable
{
    public const TABLE_NAME         = 'member_plan_assignments';
    public const ID                 = 'id';
    public const MEMBER_ID          = 'member_id';
    public const MEMBERSHIP_PLAN_ID = 'membership_plan_id';
    public const ASSIGNED_AT        = 'assigned_at';
    public const CREATED_AT         = 'created_at';
    public const UPDATED_AT         = 'updated_at';
}
