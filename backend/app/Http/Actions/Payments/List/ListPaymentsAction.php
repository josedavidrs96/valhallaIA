<?php

declare(strict_types=1);

namespace App\Http\Actions\Payments\List;

use App\Http\Actions\Payments\Shared\PaymentListResource;
use App\Src\Billing\Payment\Application\Queries\ListPayments\ListPaymentsHandler;
use App\Src\Billing\Payment\Application\Queries\ListPayments\ListPaymentsQuery;
use Illuminate\Http\JsonResponse;

final class ListPaymentsAction
{
    public function __construct(
        private readonly ListPaymentsHandler $handler,
    ) {}

    public function __invoke(ListPaymentsRequest $request): JsonResponse
    {
        $dto    = $request->getDto();
        $result = $this->handler->handle(new ListPaymentsQuery(
            memberId: $dto->memberId,
            year:     $dto->year,
            month:    $dto->month,
            page:     $dto->page,
            perPage:  $dto->perPage,
        ));

        return (new PaymentListResource($result['items'], $result['total'], $dto->page, $dto->perPage))->toResponse();
    }
}
