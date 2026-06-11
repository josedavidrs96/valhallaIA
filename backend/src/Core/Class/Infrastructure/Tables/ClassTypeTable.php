<?php

declare(strict_types=1);

namespace App\Src\Core\ClassType\Infrastructure\Tables;

final class ClassTypeTable
{
    public const TABLE_NAME = 'class_types';

    public const ID          = 'id';
    public const NAME        = 'name';
    public const SLUG        = 'slug';
    public const DESCRIPTION = 'description';
    public const COLOR       = 'color';
    public const IS_ACTIVE   = 'is_active';
    public const CREATED_AT  = 'created_at';
    public const UPDATED_AT  = 'updated_at';
}
