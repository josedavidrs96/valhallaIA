<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Infrastructure\Persistence;

use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

final class UserModel extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

    protected $table          = UserTable::TABLE_NAME;
    protected $primaryKey     = UserTable::ID;
    public    $incrementing   = false;
    protected $keyType        = 'string';

    protected $fillable = [
        UserTable::ID,
        UserTable::EMAIL,
        UserTable::PASSWORD,
        UserTable::ROLE,
        UserTable::STATUS,
        UserTable::MUST_CHANGE_PASSWORD,
        UserTable::REMEMBER_TOKEN,
    ];

    protected $hidden = [
        UserTable::PASSWORD,
        UserTable::REMEMBER_TOKEN,
    ];

    protected function casts(): array
    {
        return [
            UserTable::MUST_CHANGE_PASSWORD => 'boolean',
            UserTable::DELETED_AT           => 'datetime',
        ];
    }
}
