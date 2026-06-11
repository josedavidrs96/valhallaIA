<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Application\Queries\GetAuthenticatedUser;

use App\Src\Shared\Auth\Domain\ReadModels\AuthUserRM;
use App\Src\Shared\Auth\Domain\Repositories\UserRepositoryInterface;

final class GetAuthenticatedUserHandler
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function handle(GetAuthenticatedUserQuery $query): AuthUserRM
    {
        $user = $this->users->getById($query->userId);

        return new AuthUserRM(
            id:                 $user->id,
            email:              $user->email->value(),
            role:               $user->role,
            status:             $user->status(),
            mustChangePassword: $user->mustChangePassword(),
        );
    }
}
