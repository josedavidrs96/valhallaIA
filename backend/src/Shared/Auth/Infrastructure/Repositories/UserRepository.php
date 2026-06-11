<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Infrastructure\Repositories;

use App\Src\Shared\Auth\Domain\Entities\User;
use App\Src\Shared\Auth\Domain\Exceptions\UserNotFoundException;
use App\Src\Shared\Auth\Domain\Repositories\UserRepositoryInterface;
use App\Src\Shared\Auth\Domain\ValueObjects\UserEmail;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use App\Src\Shared\Auth\Infrastructure\Hydrators\UserHydrator;
use App\Src\Shared\Auth\Infrastructure\Persistence\UserModel;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly UserHydrator $hydrator) {}

    public function getById(UserId $id): User
    {
        $model = UserModel::query()
            ->where(UserTable::ID, $id->value())
            ->whereNull(UserTable::DELETED_AT)
            ->first();

        if ($model === null) {
            throw new UserNotFoundException("User with id '{$id->value()}' not found");
        }

        return $this->hydrator->hydrate($model);
    }

    public function findByEmail(UserEmail $email): ?User
    {
        $model = UserModel::query()
            ->whereRaw('LOWER(' . UserTable::EMAIL . ') = ?', [mb_strtolower($email->value())])
            ->whereNull(UserTable::DELETED_AT)
            ->first();

        return $model ? $this->hydrator->hydrate($model) : null;
    }

    public function save(User $user): void
    {
        UserModel::query()->updateOrCreate(
            [UserTable::ID => $user->id->value()],
            $this->hydrator->dehydrate($user),
        );
    }

    public function softDelete(UserId $id): void
    {
        UserModel::query()
            ->where(UserTable::ID, $id->value())
            ->update([UserTable::DELETED_AT => now()]);
    }
}
