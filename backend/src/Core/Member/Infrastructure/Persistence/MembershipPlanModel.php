<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Infrastructure\Persistence;

use App\Src\Core\Member\Infrastructure\Tables\MembershipPlanTable;
use Illuminate\Database\Eloquent\Model;

final class MembershipPlanModel extends Model
{
    protected $table      = MembershipPlanTable::TABLE_NAME;
    protected $primaryKey = MembershipPlanTable::ID;
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = true;
}
