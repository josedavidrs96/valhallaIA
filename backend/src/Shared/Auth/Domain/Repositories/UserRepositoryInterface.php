<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Domain\Repositories;

use App\Src\Shared\Auth\Domain\Entities\User;
use App\Src\Shared\Auth\Domain\Exceptions\UserNotFoundException;
use App\Src\Shared\Auth\Domain\ValueObjects\UserEmail;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

interface UserRepositoryInterface
{
    /** @throws UserNotFoundException */
    public function getById(UserId $id): User;

    public function findByEmail(UserEmail $email): ?User;

    public function save(User $user): void;

    public function softDelete(UserId $id): void;
}
