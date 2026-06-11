<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\Deactivate;

use App\Http\Actions\Members\Shared\MemberResource;
use App\Src\Core\Member\Application\Commands\DeactivateMember\DeactivateMemberCommand;
use App\Src\Core\Member\Application\Commands\DeactivateMember\DeactivateMemberHandler;
use App\Src\Core\Member\Application\Queries\GetMemberById\GetMemberByIdHandler;
use App\Src\Core\Member\Application\Queries\GetMemberById\GetMemberByIdQuery;
use App\Src\Core\Member\Domain\Exceptions\MemberNotFoundException;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Shared\Auth\Domain\Exceptions\InvalidStatusTransitionException;
use Illuminate\Http\JsonResponse;

final class DeactivateMemberAction
{
    public function __construct(
        private readonly DeactivateMemberHandler $handler,
        private readonly GetMemberByIdHandler    $query,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->handler->handle(new DeactivateMemberCommand(MemberId::fromString($id)));
        } catch (MemberNotFoundException) {
            return response()->json(['error' => 'Socio no encontrado', 'code' => 'MEMBER_NOT_FOUND'], 404);
        } catch (InvalidStatusTransitionException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'INVALID_STATUS_TRANSITION'], 422);
        }

        $rm = $this->query->handle(new GetMemberByIdQuery(MemberId::fromString($id)));
        return (new MemberResource($rm))->toResponse();
    }
}
