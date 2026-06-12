<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Infrastructure\Tables;

final class MembershipPlanTable
{
    public const TABLE_NAME = 'membership_plans';

    public const ID          = 'id';
    public const NAME        = 'name';
    public const SLUG        = 'slug';
    public const DESCRIPTION = 'description';
    public const PRICE_CENTS = 'price_cents';
    public const CLASSES_PER_MONTH   = 'classes_per_month';
    public const MAX_WEEKLY_SESSIONS = 'max_weekly_sessions';
    public const IS_ACTIVE           = 'is_active';
    public const CREATED_AT  = 'created_at';
    public const UPDATED_AT  = 'updated_at';
}
