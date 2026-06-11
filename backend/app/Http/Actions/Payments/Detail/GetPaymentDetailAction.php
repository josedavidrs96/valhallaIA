<?php

declare(strict_types=1);

namespace App\Http\Actions\Payments\Detail;

use App\Http\Actions\Payments\Shared\PaymentResource;
use App\Src\Billing\Payment\Application\Queries\GetPaymentById\GetPaymentByIdHandler;
use App\Src\Billing\Payment\Application\Queries\GetPaymentById\GetPaymentByIdQuery;
use App\Src\Billing\Payment\Domain\Exceptions\PaymentNotFoundException;
use App\Src\Billing\Payment\Domain\ValueObjects\PaymentId;
use Illuminate\Http\JsonResponse;

final class GetPaymentDetailAction
{
    public function __construct(
        private readonly GetPaymentByIdHandler $handler,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $rm = $this->handler->handle(new GetPaymentByIdQuery(PaymentId::fromString($id)));
        } catch (PaymentNotFoundException) {
            return response()->json(['error' => 'Pago no encontrado', 'code' => 'PAYMENT_NOT_FOUND'], 404);
        }

        return (new PaymentResource($rm))->toResponse();
    }
}
