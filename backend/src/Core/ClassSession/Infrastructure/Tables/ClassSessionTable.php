<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Infrastructure\Tables;

final class ClassSessionTable
{
    public const TABLE_NAME    = 'class_sessions';
    public const ID            = 'id';
    public const CLASS_TYPE_ID = 'class_type_id';
    public const COACH_ID      = 'coach_id';
    public const DAY_OF_WEEK   = 'day_of_week';
    public const TIME_SLOT     = 'time_slot';
    public const MAX_CAPACITY  = 'max_capacity';
    public const STATUS        = 'status';
    public const CREATED_AT    = 'created_at';
    public const UPDATED_AT    = 'updated_at';
    public const DELETED_AT    = 'deleted_at';
}
