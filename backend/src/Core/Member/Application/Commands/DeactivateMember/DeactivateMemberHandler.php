<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Application\Commands\DeactivateMember;

use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;
use App\Src\Shared\Auth\Domain\Repositories\UserRepositoryInterface;
use App\Src\Shared\Auth\Infrastructure\Persistence\UserModel;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;

final class DeactivateMemberHandler
{
    public function __construct(
        private readonly MemberRepositoryInterface $memberRepository,
        private readonly UserRepositoryInterface   $userRepository,
    ) {}

    public function handle(DeactivateMemberCommand $command): void
    {
        $member = $this->memberRepository->getById($command->memberId);
        $user   = $this->userRepository->getById($member->userId);

        $user->deactivate();
        $this->userRepository->save($user);

        // Revoke all Sanctum tokens (infrastructure concern — acceptable here)
        UserModel::query()
            ->where(UserTable::ID, $member->userId->value())
            ->first()
            ?->tokens()
            ->delete();
    }
}
