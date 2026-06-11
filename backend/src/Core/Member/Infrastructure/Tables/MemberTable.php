<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Infrastructure\Tables;

final class MemberTable
{
    public const TABLE_NAME = 'members';

    public const ID                      = 'id';
    public const USER_ID                 = 'user_id';
    public const MEMBER_NUMBER           = 'member_number';
    public const FIRST_NAME              = 'first_name';
    public const LAST_NAME               = 'last_name';
    public const PHONE                   = 'phone';
    public const DATE_OF_BIRTH           = 'date_of_birth';
    public const PROFILE_PHOTO           = 'profile_photo';
    public const JOIN_DATE               = 'join_date';
    public const EMERGENCY_CONTACT_NAME  = 'emergency_contact_name';
    public const EMERGENCY_CONTACT_PHONE = 'emergency_contact_phone';
    public const NOTES                   = 'notes';
    public const DELETED_AT              = 'deleted_at';
    public const CREATED_AT              = 'created_at';
    public const UPDATED_AT              = 'updated_at';
}
