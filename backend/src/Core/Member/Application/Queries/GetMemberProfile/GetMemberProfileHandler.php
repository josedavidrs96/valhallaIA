<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Application\Queries\GetMemberProfile;

use App\Src\Core\Member\Domain\ReadModels\MemberDetailRM;
use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;

final class GetMemberProfileHandler
{
    public function __construct(private readonly MemberRepositoryInterface $memberRepository) {}

    public function handle(GetMemberProfileQuery $query): MemberDetailRM
    {
        return $this->memberRepository->getDetailByUserId($query->userId);
    }
}
