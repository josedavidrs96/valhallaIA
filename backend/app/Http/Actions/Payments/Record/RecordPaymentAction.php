<?php

declare(strict_types=1);

namespace App\Http\Actions\Payments\Record;

use App\Http\Actions\Payments\Shared\PaymentResource;
use App\Src\Billing\Payment\Application\Commands\RecordPayment\RecordPaymentCommand;
use App\Src\Billing\Payment\Application\Commands\RecordPayment\RecordPaymentHandler;
use App\Src\Billing\Payment\Application\Queries\GetPaymentById\GetPaymentByIdHandler;
use App\Src\Billing\Payment\Application\Queries\GetPaymentById\GetPaymentByIdQuery;
use App\Src\Billing\Payment\Domain\Exceptions\InvalidPaymentAmountException;
use App\Src\Billing\Payment\Domain\Exceptions\PaymentAlreadyExistsForMonthException;
use App\Src\Billing\Payment\Domain\ValueObjects\PaymentId;
use App\Src\Core\Member\Domain\Exceptions\MemberNotFoundException;
use App\Src\Core\Member\Domain\Exceptions\MembershipPlanNotFoundException;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use App\Src\Core\Member\Domain\ValueObjects\MembershipPlanId;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;

final class RecordPaymentAction
{
    public function __construct(
        private readonly RecordPaymentHandler  $handler,
        private readonly GetPaymentByIdHandler $query,
    ) {}

    public function __invoke(RecordPaymentRequest $request): JsonResponse
    {
        $dto       = $request->getDto();
        $paymentId = PaymentId::random();

        try {
            $this->handler->handle(new RecordPaymentCommand(
                paymentId:        $paymentId,
                memberId:         MemberId::fromString($dto->memberId),
                membershipPlanId: MembershipPlanId::fromString($dto->membershipPlanId),
                recordedBy:       UserId::fromString((string) $request->user()->id),
                amountCents:      $dto->amountCents,
                paymentDate:      $dto->paymentDate,
                notes:            $dto->notes,
            ));
        } catch (MemberNotFoundException) {
            return response()->json(['error' => 'Socio no encontrado', 'code' => 'MEMBER_NOT_FOUND'], 404);
        } catch (MembershipPlanNotFoundException) {
            return response()->json(['error' => 'Plan no encontrado', 'code' => 'MEMBERSHIP_PLAN_NOT_FOUND'], 422);
        } catch (PaymentAlreadyExistsForMonthException) {
            return response()->json(['error' => 'Ya existe un pago para este mes', 'code' => 'PAYMENT_ALREADY_EXISTS_FOR_MONTH'], 409);
        } catch (InvalidPaymentAmountException) {
            return response()->json(['error' => 'El importe debe ser mayor que cero', 'code' => 'INVALID_PAYMENT_AMOUNT'], 422);
        }

        $rm = $this->query->handle(new GetPaymentByIdQuery($paymentId));

        return (new PaymentResource($rm))->toResponse(201);
    }
}
