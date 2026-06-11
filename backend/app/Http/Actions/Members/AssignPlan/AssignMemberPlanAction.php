<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\AssignPlan;

use App\Http\Actions\Members\Shared\MemberResource;
use App\Src\Core\Member\Application\Commands\AssignMembershipPlan\AssignMembershipPlanCommand;
use App\Src\Core\Member\Application\Commands\AssignMembershipPlan\AssignMembershipPlanHandler;
use App\Src\Core\Member\Application\Queries\GetMemberById\GetMemberByIdHandler;
use App\Src\Core\Member\Application\Queries\GetMemberById\GetMemberByIdQuery;
use App\Src\Core\Member\Domain\Exceptions\MemberNotFoundException;
use App\Src\Core\Member\Domain\Exceptions\MembershipPlanNotFoundException;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Core\Member\Domain\ValueObjects\MembershipPlanId;
use Illuminate\Http\JsonResponse;

final class AssignMemberPlanAction
{
    public function __construct(
        private readonly AssignMembershipPlanHandler $handler,
        private readonly GetMemberByIdHandler        $query,
    ) {}

    public function __invoke(AssignMemberPlanRequest $request, string $id): JsonResponse
    {
        $dto = $request->getDto();

        try {
            $this->handler->handle(new AssignMembershipPlanCommand(
                memberId: MemberId::fromString($id),
                planId:   MembershipPlanId::fromString($dto->planId),
            ));
        } catch (MemberNotFoundException) {
            return response()->json(['error' => 'Socio no encontrado', 'code' => 'MEMBER_NOT_FOUND'], 404);
        } catch (MembershipPlanNotFoundException) {
            return response()->json(['error' => 'Plan de membresia no encontrado', 'code' => 'MEMBERSHIP_PLAN_NOT_FOUND'], 422);
        }

        $rm = $this->query->handle(new GetMemberByIdQuery(MemberId::fromString($id)));
        return (new MemberResource($rm))->toResponse();
    }
}
