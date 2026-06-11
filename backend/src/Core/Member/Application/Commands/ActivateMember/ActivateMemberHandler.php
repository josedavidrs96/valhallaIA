<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Application\Commands\ActivateMember;

use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;
use App\Src\Shared\Auth\Domain\Enums\UserStatus;
use App\Src\Shared\Auth\Domain\Repositories\UserRepositoryInterface;

final class ActivateMemberHandler
{
    public function __construct(
        private readonly MemberRepositoryInterface $memberRepository,
        private readonly UserRepositoryInterface   $userRepository,
    ) {}

    public function handle(ActivateMemberCommand $command): void
    {
        $member = $this->memberRepository->getById($command->memberId);
        $user   = $this->userRepository->getById($member->userId);

        if ($user->status() === UserStatus::PendingApproval) {
            $user->approve();
        } else {
            $user->activate();
        }

        $this->userRepository->save($user);
    }
}
