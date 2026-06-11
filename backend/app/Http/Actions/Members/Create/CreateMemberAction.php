<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\Create;

use App\Http\Actions\Members\Shared\MemberResource;
use App\Src\Core\Member\Application\Commands\CreateMember\CreateMemberCommand;
use App\Src\Core\Member\Application\Commands\CreateMember\CreateMemberHandler;
use App\Src\Core\Member\Application\Queries\GetMemberById\GetMemberByIdHandler;
use App\Src\Core\Member\Application\Queries\GetMemberById\GetMemberByIdQuery;
use App\Src\Core\Member\Domain\Exceptions\MemberEmailAlreadyExistsException;
use App\Src\Core\Member\Domain\Exceptions\MembershipPlanNotFoundException;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Core\Member\Domain\ValueObjects\MembershipPlanId;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;

final class CreateMemberAction
{
    public function __construct(
        private readonly CreateMemberHandler  $handler,
        private readonly GetMemberByIdHandler $query,
    ) {}

    public function __invoke(CreateMemberRequest $request): JsonResponse
    {
        $dto      = $request->getDto();
        $memberId = MemberId::random();
        $userId   = UserId::random();

        try {
            $this->handler->handle(new CreateMemberCommand(
                memberId:    $memberId,
                userId:      $userId,
                email:       $dto->email,
                firstName:   $dto->firstName,
                lastName:    $dto->lastName,
                joinDate:    $dto->joinDate,
                planId:      MembershipPlanId::fromString($dto->planId),
                phone:       $dto->phone,
                dateOfBirth: $dto->dateOfBirth,
            ));
        } catch (MemberEmailAlreadyExistsException) {
            return response()->json(['error' => 'El email ya esta registrado', 'code' => 'MEMBER_EMAIL_ALREADY_EXISTS'], 409);
        } catch (MembershipPlanNotFoundException) {
            return response()->json(['error' => 'Plan de membresia no encontrado', 'code' => 'MEMBERSHIP_PLAN_NOT_FOUND'], 422);
        }

        $rm = $this->query->handle(new GetMemberByIdQuery($memberId));
        return (new MemberResource($rm))->toResponse(201);
    }
}
