<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Infrastructure\Persistence;

use App\Src\Core\Member\Infrastructure\Tables\MemberTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class MemberModel extends Model
{
    use SoftDeletes;

    protected $table      = MemberTable::TABLE_NAME;
    protected $primaryKey = MemberTable::ID;
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        MemberTable::ID,
        MemberTable::USER_ID,
        MemberTable::MEMBER_NUMBER,
        MemberTable::FIRST_NAME,
        MemberTable::LAST_NAME,
        MemberTable::PHONE,
        MemberTable::DATE_OF_BIRTH,
        MemberTable::PROFILE_PHOTO,
        MemberTable::JOIN_DATE,
        MemberTable::EMERGENCY_CONTACT_NAME,
        MemberTable::EMERGENCY_CONTACT_PHONE,
        MemberTable::NOTES,
    ];

    protected function casts(): array
    {
        return [
            MemberTable::DATE_OF_BIRTH => 'date',
            MemberTable::DELETED_AT    => 'datetime',
        ];
    }
}
