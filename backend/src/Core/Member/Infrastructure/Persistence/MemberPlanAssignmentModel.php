<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Infrastructure\Persistence;

use App\Src\Core\Member\Infrastructure\Tables\MemberPlanAssignmentTable;
use Illuminate\Database\Eloquent\Model;

final class MemberPlanAssignmentModel extends Model
{
    protected $table      = MemberPlanAssignmentTable::TABLE_NAME;
    protected $primaryKey = MemberPlanAssignmentTable::ID;
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        MemberPlanAssignmentTable::ID,
        MemberPlanAssignmentTable::MEMBER_ID,
        MemberPlanAssignmentTable::MEMBERSHIP_PLAN_ID,
        MemberPlanAssignmentTable::ASSIGNED_AT,
    ];
}
