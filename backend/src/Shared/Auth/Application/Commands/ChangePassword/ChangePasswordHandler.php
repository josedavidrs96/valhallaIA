<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Application\Commands\ChangePassword;

use App\Src\Shared\Auth\Domain\Exceptions\WrongCurrentPasswordException;
use App\Src\Shared\Auth\Domain\Repositories\UserRepositoryInterface;
use App\Src\Shared\Auth\Domain\ValueObjects\HashedPassword;

final class ChangePasswordHandler
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function handle(ChangePasswordCommand $command): void
    {
        $user = $this->users->getById($command->userId);

        if (!$user->password()->verify($command->currentPassword)) {
            throw new WrongCurrentPasswordException();
        }

        $newHashed = HashedPassword::fromPlainText($command->newPassword);

        $user->changePassword($newHashed, $command->newPassword);
        $user->clearPasswordChangeFlag();

        $this->users->save($user);
    }
}
