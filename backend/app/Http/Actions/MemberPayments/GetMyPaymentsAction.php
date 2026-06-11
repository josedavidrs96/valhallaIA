<?php

declare(strict_types=1);

namespace App\Http\Actions\MemberPayments;

use App\Http\Actions\Payments\Shared\MemberPaymentListResource;
use App\Src\Billing\Payment\Application\Queries\GetMyPayments\GetMyPaymentsHandler;
use App\Src\Billing\Payment\Application\Queries\GetMyPayments\GetMyPaymentsQuery;
use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetMyPaymentsAction
{
    public function __construct(
        private readonly GetMyPaymentsHandler      $handler,
        private readonly MemberRepositoryInterface $memberRepo,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $userId = UserId::fromString((string) $request->user()->id);
        $member = $this->memberRepo->findByUserId($userId);

        if ($member === null) {
            return response()->json(['error' => 'Socio no encontrado', 'code' => 'MEMBER_NOT_FOUND'], 404);
        }

        $items = $this->handler->handle(new GetMyPaymentsQuery($member->id));

        return (new MemberPaymentListResource($items))->toResponse();
    }
}
