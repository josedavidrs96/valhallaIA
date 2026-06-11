<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Infrastructure\Tables;

final class UserTable
{
    public const TABLE_NAME = 'users';

    public const ID                   = 'id';
    public const EMAIL                = 'email';
    public const PASSWORD             = 'password';
    public const ROLE                 = 'role';
    public const STATUS               = 'status';
    public const MUST_CHANGE_PASSWORD = 'must_change_password';
    public const REMEMBER_TOKEN       = 'remember_token';
    public const DELETED_AT           = 'deleted_at';
    public const CREATED_AT           = 'created_at';
    public const UPDATED_AT           = 'updated_at';
}
