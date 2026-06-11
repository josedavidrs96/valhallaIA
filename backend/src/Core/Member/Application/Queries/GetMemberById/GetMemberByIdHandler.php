<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Application\Queries\GetMemberById;

use App\Src\Core\Member\Domain\ReadModels\MemberDetailRM;
use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;

final class GetMemberByIdHandler
{
    public function __construct(private readonly MemberRepositoryInterface $memberRepository) {}

    public function handle(GetMemberByIdQuery $query): MemberDetailRM
    {
        return $this->memberRepository->getDetailById($query->memberId);
    }
}
