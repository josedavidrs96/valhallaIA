<?php

declare(strict_types=1);

namespace App\Src\Core\Member\Domain\Repositories;

use App\Src\Core\Member\Domain\Entities\Member;
use App\Src\Core\Member\Domain\Exceptions\MemberNotFoundException;
use App\Src\Core\Member\Domain\ReadModels\MemberDetailRM;
use App\Src\Core\Member\Domain\ReadModels\MemberListItemRM;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

interface MemberRepositoryInterface
{
    /**
     * @throws MemberNotFoundException
     */
    public function getById(MemberId $id): Member;

    public function findByUserId(UserId $userId): ?Member;

    public function save(Member $member): void;

    public function nextMemberNumber(): int;

    /**
     * @return MemberListItemRM[]
     */
    public function findAll(?string $status, ?string $planId, ?string $search, int $page, int $perPage): array;

    public function countAll(?string $status, ?string $planId, ?string $search = null): int;

    /**
     * @throws MemberNotFoundException
     */
    public function getDetailById(MemberId $id): MemberDetailRM;

    /**
     * @throws MemberNotFoundException
     */
    public function getDetailByUserId(UserId $userId): MemberDetailRM;
}
