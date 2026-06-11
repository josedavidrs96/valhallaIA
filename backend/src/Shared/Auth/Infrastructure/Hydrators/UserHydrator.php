<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Infrastructure\Hydrators;

use App\Src\Shared\Auth\Domain\Entities\User;
use App\Src\Shared\Auth\Domain\Enums\UserRole;
use App\Src\Shared\Auth\Domain\Enums\UserStatus;
use App\Src\Shared\Auth\Domain\ValueObjects\HashedPassword;
use App\Src\Shared\Auth\Domain\ValueObjects\UserEmail;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use App\Src\Shared\Auth\Infrastructure\Persistence\UserModel;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;

final class UserHydrator
{
    public function hydrate(UserModel $model): User
    {
        return new User(
            id:                 UserId::fromString($model->{UserTable::ID}),
            email:              new UserEmail($model->{UserTable::EMAIL}),
            password:           HashedPassword::fromHash($model->{UserTable::PASSWORD}),
            role:               UserRole::from($model->{UserTable::ROLE}),
            status:             UserStatus::from($model->{UserTable::STATUS}),
            mustChangePassword: (bool) $model->{UserTable::MUST_CHANGE_PASSWORD},
            createdAt:          new \DateTimeImmutable((string) $model->{UserTable::CREATED_AT}),
            deletedAt:          $model->{UserTable::DELETED_AT}
                                    ? new \DateTimeImmutable((string) $model->{UserTable::DELETED_AT})
                                    : null,
        );
    }

    public function dehydrate(User $user): array
    {
        return [
            UserTable::ID                   => $user->id->value(),
            UserTable::EMAIL                => $user->email->value(),
            UserTable::PASSWORD             => $user->password()->value(),
            UserTable::ROLE                 => $user->role->value,
            UserTable::STATUS               => $user->status()->value,
            UserTable::MUST_CHANGE_PASSWORD => $user->mustChangePassword() ? 1 : 0,
            UserTable::DELETED_AT           => $user->deletedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
