<?php

declare(strict_types=1);

namespace App\Src\Core\Staff\Infrastructure\Tables;

final class StaffTable
{
    public const TABLE_NAME = 'staff';

    public const ID             = 'id';
    public const USER_ID        = 'user_id';
    public const FIRST_NAME     = 'first_name';
    public const LAST_NAME      = 'last_name';
    public const PHONE          = 'phone';
    public const SPECIALIZATION = 'specialization';
    public const HIRE_DATE      = 'hire_date';
    public const CREATED_AT     = 'created_at';
    public const UPDATED_AT     = 'updated_at';
}
