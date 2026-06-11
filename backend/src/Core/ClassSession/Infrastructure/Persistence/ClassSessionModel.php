<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Infrastructure\Persistence;

use App\Src\Core\ClassSession\Infrastructure\Tables\ClassSessionTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ClassSessionModel extends Model
{
    use SoftDeletes;

    protected $table        = ClassSessionTable::TABLE_NAME;
    protected $primaryKey   = ClassSessionTable::ID;
    public    $incrementing = false;
    protected $keyType      = 'string';

    protected $fillable = [
        ClassSessionTable::ID,
        ClassSessionTable::CLASS_TYPE_ID,
        ClassSessionTable::COACH_ID,
        ClassSessionTable::DAY_OF_WEEK,
        ClassSessionTable::TIME_SLOT,
        ClassSessionTable::MAX_CAPACITY,
        ClassSessionTable::STATUS,
    ];

    protected function casts(): array
    {
        return [
            ClassSessionTable::DELETED_AT => 'datetime',
        ];
    }
}
