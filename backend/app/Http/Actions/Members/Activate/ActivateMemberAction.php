<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\Activate;

use App\Http\Actions\Members\Shared\MemberResource;
use App\Src\Core\Member\Application\Commands\ActivateMember\ActivateMemberCommand;
use App\Src\Core\Member\Application\Commands\ActivateMember\ActivateMemberHandler;
use App\Src\Core\Member\Application\Queries\GetMemberById\GetMemberByIdHandler;
use App\Src\Core\Member\Application\Queries\GetMemberById\GetMemberByIdQuery;
use App\Src\Core\Member\Domain\Exceptions\MemberNotFoundException;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Shared\Auth\Domain\Exceptions\InvalidStatusTransitionException;
use Illuminate\Http\JsonResponse;

final class ActivateMemberAction
{
    public function __construct(
        private readonly ActivateMemberHandler $handler,
        private readonly GetMemberByIdHandler  $query,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->handler->handle(new ActivateMemberCommand(MemberId::fromString($id)));
        } catch (MemberNotFoundException) {
            return response()->json(['error' => 'Socio no encontrado', 'code' => 'MEMBER_NOT_FOUND'], 404);
        } catch (InvalidStatusTransitionException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'INVALID_STATUS_TRANSITION'], 422);
        }

        $rm = $this->query->handle(new GetMemberByIdQuery(MemberId::fromString($id)));
        return (new MemberResource($rm))->toResponse();
    }
}
