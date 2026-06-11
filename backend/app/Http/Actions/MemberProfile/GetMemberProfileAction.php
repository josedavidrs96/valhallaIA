<?php

declare(strict_types=1);

namespace App\Http\Actions\MemberProfile;

use App\Http\Actions\Members\Shared\MemberResource;
use App\Src\Core\Member\Application\Queries\GetMemberProfile\GetMemberProfileHandler;
use App\Src\Core\Member\Application\Queries\GetMemberProfile\GetMemberProfileQuery;
use App\Src\Core\Member\Domain\Exceptions\MemberNotFoundException;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetMemberProfileAction
{
    public function __construct(private readonly GetMemberProfileHandler $handler) {}

    public function __invoke(Request $request): JsonResponse
    {
        $userId = UserId::fromString($request->user()->id);

        try {
            $rm = $this->handler->handle(new GetMemberProfileQuery($userId));
        } catch (MemberNotFoundException) {
            return response()->json(['error' => 'Perfil no encontrado', 'code' => 'MEMBER_NOT_FOUND'], 404);
        }

        return (new MemberResource($rm))->toResponse();
    }
}
