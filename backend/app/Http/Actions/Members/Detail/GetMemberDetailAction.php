<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\Detail;

use App\Http\Actions\Members\Shared\MemberResource;
use App\Src\Core\Member\Application\Queries\GetMemberById\GetMemberByIdHandler;
use App\Src\Core\Member\Application\Queries\GetMemberById\GetMemberByIdQuery;
use App\Src\Core\Member\Domain\Exceptions\MemberNotFoundException;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use Illuminate\Http\JsonResponse;

final class GetMemberDetailAction
{
    public function __construct(private readonly GetMemberByIdHandler $handler) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $rm = $this->handler->handle(new GetMemberByIdQuery(MemberId::fromString($id)));
        } catch (MemberNotFoundException) {
            return response()->json(['error' => 'Socio no encontrado', 'code' => 'MEMBER_NOT_FOUND'], 404);
        }

        return (new MemberResource($rm))->toResponse();
    }
}
