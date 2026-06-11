<?php

declare(strict_types=1);

namespace App\Http\Actions\Members\Update;

use App\Http\Actions\Members\Shared\MemberResource;
use App\Src\Core\Member\Application\Commands\UpdateMember\UpdateMemberCommand;
use App\Src\Core\Member\Application\Commands\UpdateMember\UpdateMemberHandler;
use App\Src\Core\Member\Application\Queries\GetMemberById\GetMemberByIdHandler;
use App\Src\Core\Member\Application\Queries\GetMemberById\GetMemberByIdQuery;
use App\Src\Core\Member\Domain\Exceptions\MemberNotFoundException;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use Illuminate\Http\JsonResponse;

final class UpdateMemberAction
{
    public function __construct(
        private readonly UpdateMemberHandler  $handler,
        private readonly GetMemberByIdHandler $query,
    ) {}

    public function __invoke(UpdateMemberRequest $request, string $id): JsonResponse
    {
        $dto = $request->getDto();

        try {
            $this->handler->handle(new UpdateMemberCommand(
                memberId:             MemberId::fromString($id),
                firstName:            $dto->firstName,
                lastName:             $dto->lastName,
                phone:                $dto->phone,
                dateOfBirth:          $dto->dateOfBirth,
                emergencyContactName: $dto->emergencyContactName,
                emergencyContactPhone: $dto->emergencyContactPhone,
                notes:                $dto->notes,
                profilePhoto:         $dto->profilePhoto,
            ));
        } catch (MemberNotFoundException) {
            return response()->json(['error' => 'Socio no encontrado', 'code' => 'MEMBER_NOT_FOUND'], 404);
        }

        $rm = $this->query->handle(new GetMemberByIdQuery(MemberId::fromString($id)));
        return (new MemberResource($rm))->toResponse();
    }
}
