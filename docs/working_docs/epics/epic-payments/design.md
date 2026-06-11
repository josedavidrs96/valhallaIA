# Solution Design: Payment Tracking (epic-payments)

**Requirement:** [requirements.md](requirements.md)
**Validation:** [validation.md](validation.md)
**Date:** 2026-06-11
**Status:** Draft
**Bounded Context:** `Billing/Payment`

---

## Summary

Build the `Billing/Payment` bounded context from scratch. A single `payments` table is created. The `Payment` entity is immutable (no state machine). The overdue members query crosses bounded context boundaries by reading from the `members` + `users` tables in one SQL query — this is acceptable because it is a read-only cross-BC join in a single-database single-service application.

All patterns follow the established `Core/Member` precedent: ULID-based Value Objects, Hydrator pattern, Repository interface + implementation, thin Actions with explicit exception handling, Requests with only `getDto()`.

---

## Architecture Decision

**Payment is in `Billing/Payment` BC.** This matches the roadmap and keeps billing concerns separate from member profile concerns.

**Cross-BC read for overdue list.** The overdue query (`GET /api/admin/payments/overdue`) requires joining members + users + payments. In a microservices setup this would require a query bus call to Core/Member. In this single-service application a direct SQL join in the PaymentRepository is acceptable and avoids unnecessary complexity. The join is read-only and does not mutate the Member BC.

**Payments are immutable.** No `update()` or `delete()` methods exist on the entity or repository in MVP. This eliminates the need for a status machine and simplifies all handlers.

**billing_month is derived, never user-supplied.** The `Payment` entity's `create()` factory derives `billing_month` from `payment_date` internally. The HTTP layer never accepts `billing_month` as input.

**No Command/Query Bus.** Consistent with the existing pattern (Actions directly inject Handlers). The project does not yet have a Bus infrastructure.

**Amount in cents.** The `amount_cents` column stores integers to avoid floating-point precision issues. All display formatting happens in the Resource layer.

---

## Existing Code Analysis

| Component | Location | Reusable | Modifications Needed |
|-----------|----------|----------|---------------------|
| MemberId | `src/Core/Member/Domain/ValueObjects/MemberId.php` | Yes | None — reference by value |
| MembershipPlanId | `src/Core/Member/Domain/ValueObjects/MembershipPlanId.php` | Yes | None — reference by value |
| UserId | `src/Shared/Auth/Domain/ValueObjects/UserId.php` | Yes | None |
| MemberRepositoryInterface | `src/Core/Member/Domain/Repositories/MemberRepositoryInterface.php` | No — need new method | Add `findAllActiveWithCurrentPlan(): ActiveMemberSummary[]` for overdue logic |
| LoginAction pattern | `app/Http/Actions/Auth/Login/LoginAction.php` | Reference | Follow same pattern |
| api.php | `routes/api.php` | Extend | Add new route groups |
| AppServiceProvider | `app/Providers/AppServiceProvider.php` | Extend | Add PaymentRepositoryInterface binding |

---

## Implementation Plan

### 1. Domain Layer — `backend/src/Billing/Payment/`

#### Value Objects

| VO | File Path | Description |
|----|-----------|-------------|
| PaymentId | `src/Billing/Payment/Domain/ValueObjects/PaymentId.php` | ULID-based ID for Payment entity |

```php
final class PaymentId extends Ulid {
    public static function random(): static { return new static(); }
    public static function fromString(string $value): static { return new static($value); }
    public function value(): string { return $this->toBase32(); }
}
```

#### Entities

| Entity | File Path | Description |
|--------|-----------|-------------|
| Payment | `src/Billing/Payment/Domain/Entities/Payment.php` | Immutable cash payment record |

```php
final class Payment {
    public function __construct(
        public readonly PaymentId        $id,
        public readonly MemberId         $memberId,
        public readonly MembershipPlanId $membershipPlanId,
        public readonly UserId           $recordedBy,   // admin who recorded it
        public readonly int              $amountCents,  // e.g. 4000 = €40.00
        public readonly \DateTimeImmutable $paymentDate,
        public readonly string           $billingMonth, // YYYY-MM, derived from paymentDate
        public readonly ?string          $notes,
        public readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        PaymentId        $id,
        MemberId         $memberId,
        MembershipPlanId $membershipPlanId,
        UserId           $recordedBy,
        int              $amountCents,
        \DateTimeImmutable $paymentDate,
        ?string          $notes = null,
    ): self {
        if ($amountCents <= 0) {
            throw new InvalidPaymentAmountException('Payment amount must be greater than zero');
        }

        return new self(
            id:               $id,
            memberId:         $memberId,
            membershipPlanId: $membershipPlanId,
            recordedBy:       $recordedBy,
            amountCents:      $amountCents,
            paymentDate:      $paymentDate,
            billingMonth:     $paymentDate->format('Y-m'), // derived here — never from input
            notes:            $notes,
            createdAt:        new \DateTimeImmutable(),
        );
    }
}
```

#### Exceptions

| Exception | File Path | HTTP Status |
|-----------|-----------|-------------|
| PaymentNotFoundException | `src/Billing/Payment/Domain/Exceptions/PaymentNotFoundException.php` | 404 |
| PaymentAlreadyExistsForMonthException | `src/Billing/Payment/Domain/Exceptions/PaymentAlreadyExistsForMonthException.php` | 409 |
| InvalidPaymentAmountException | `src/Billing/Payment/Domain/Exceptions/InvalidPaymentAmountException.php` | 422 |

#### Read Models

| ReadModel | File Path | Used By |
|-----------|-----------|---------|
| PaymentDetailRM | `src/Billing/Payment/Domain/ReadModels/PaymentDetailRM.php` | GetPaymentByIdHandler, RecordPaymentAction |
| PaymentListItemRM | `src/Billing/Payment/Domain/ReadModels/PaymentListItemRM.php` | ListPaymentsHandler |
| OverdueMemberRM | `src/Billing/Payment/Domain/ReadModels/OverdueMemberRM.php` | GetOverdueMembersHandler |
| MemberPaymentListItemRM | `src/Billing/Payment/Domain/ReadModels/MemberPaymentListItemRM.php` | GetMyPaymentsHandler (member self-service) |

```php
// PaymentDetailRM — full detail including joined member and plan data
final readonly class PaymentDetailRM {
    public function __construct(
        public string  $id,
        public string  $memberId,
        public int     $memberNumber,
        public string  $memberFirstName,
        public string  $memberLastName,
        public string  $membershipPlanId,
        public string  $planName,
        public string  $recordedBy,       // UserId of admin
        public int     $amountCents,
        public string  $paymentDate,      // Y-m-d
        public string  $billingMonth,     // YYYY-MM
        public ?string $notes,
        public string  $createdAt,
    ) {}
}

// PaymentListItemRM — lightweight row for admin list
final readonly class PaymentListItemRM {
    public function __construct(
        public string $id,
        public int    $memberNumber,
        public string $memberFirstName,
        public string $memberLastName,
        public int    $amountCents,
        public string $paymentDate,
        public string $billingMonth,
        public string $planName,
        public string $createdAt,
    ) {}
}

// OverdueMemberRM — active member missing payment for current month
final readonly class OverdueMemberRM {
    public function __construct(
        public string  $memberId,
        public int     $memberNumber,
        public string  $firstName,
        public string  $lastName,
        public string  $email,
        public ?string $planName,
        public ?string $lastPaymentDate,  // null if never paid
    ) {}
}

// MemberPaymentListItemRM — member's own payment history row
final readonly class MemberPaymentListItemRM {
    public function __construct(
        public string $id,
        public int    $amountCents,
        public string $paymentDate,
        public string $billingMonth,
        public string $planName,
    ) {}
}
```

#### Repository Interface

```php
// src/Billing/Payment/Domain/Repositories/PaymentRepositoryInterface.php

interface PaymentRepositoryInterface {
    /** @throws PaymentNotFoundException */
    public function getById(PaymentId $id): Payment;

    /** @throws PaymentNotFoundException */
    public function getDetailById(PaymentId $id): PaymentDetailRM;

    public function findByMemberAndBillingMonth(MemberId $memberId, string $billingMonth): ?Payment;

    public function save(Payment $payment): void;

    /** @return PaymentListItemRM[] */
    public function findAll(?string $memberId, ?int $year, ?int $month, int $page, int $perPage): array;

    public function countAll(?string $memberId, ?int $year, ?int $month): int;

    /** @return OverdueMemberRM[] — active members with no payment for given billing month */
    public function findOverdueMembers(string $billingMonth): array;

    /** @return MemberPaymentListItemRM[] */
    public function findByMemberId(MemberId $memberId): array;
}
```

---

### 2. Infrastructure Layer — `backend/src/Billing/Payment/Infrastructure/`

#### Table Constants

| Class | File Path | Table |
|-------|-----------|-------|
| PaymentTable | `src/Billing/Payment/Infrastructure/Tables/PaymentTable.php` | `payments` |

```php
final class PaymentTable {
    public const TABLE_NAME          = 'payments';
    public const ID                  = 'id';
    public const MEMBER_ID           = 'member_id';
    public const MEMBERSHIP_PLAN_ID  = 'membership_plan_id';
    public const RECORDED_BY         = 'recorded_by';
    public const AMOUNT_CENTS        = 'amount_cents';
    public const PAYMENT_DATE        = 'payment_date';
    public const BILLING_MONTH       = 'billing_month';
    public const NOTES               = 'notes';
    public const CREATED_AT          = 'created_at';
    public const UPDATED_AT          = 'updated_at';
}
```

#### Migrations

| # | Migration File | Description |
|---|---------------|-------------|
| 1 | `2026_06_11_000001_create_payments_table.php` | Create `payments` table with unique constraint on (member_id, billing_month) |

```sql
CREATE TABLE payments (
    id                  CHAR(26)    PRIMARY KEY,
    member_id           CHAR(26)    NOT NULL,
    membership_plan_id  CHAR(26)    NOT NULL,
    recorded_by         CHAR(26)    NOT NULL,
    amount_cents        INT         NOT NULL,
    payment_date        DATE        NOT NULL,
    billing_month       CHAR(7)     NOT NULL,  -- YYYY-MM
    notes               TEXT        NULL,
    created_at          TIMESTAMP   NULL,
    updated_at          TIMESTAMP   NULL,
    FOREIGN KEY (member_id)          REFERENCES members(id),
    FOREIGN KEY (membership_plan_id) REFERENCES membership_plans(id),
    FOREIGN KEY (recorded_by)        REFERENCES users(id),
    UNIQUE KEY uq_member_billing_month (member_id, billing_month),
    INDEX idx_billing_month (billing_month),
    INDEX idx_member_id (member_id),
    INDEX idx_payment_date (payment_date)
);
```

#### Eloquent Model

| Model | File Path |
|-------|-----------|
| PaymentModel | `src/Billing/Payment/Infrastructure/Persistence/PaymentModel.php` |

```php
final class PaymentModel extends Model {
    protected $table      = PaymentTable::TABLE_NAME;
    protected $primaryKey = PaymentTable::ID;
    public $incrementing  = false;
    protected $keyType    = 'string';
    protected $fillable   = [
        PaymentTable::ID, PaymentTable::MEMBER_ID, PaymentTable::MEMBERSHIP_PLAN_ID,
        PaymentTable::RECORDED_BY, PaymentTable::AMOUNT_CENTS, PaymentTable::PAYMENT_DATE,
        PaymentTable::BILLING_MONTH, PaymentTable::NOTES,
    ];
    protected function casts(): array {
        return [PaymentTable::PAYMENT_DATE => 'date'];
    }
}
```

#### Hydrator

| Hydrator | File Path |
|----------|-----------|
| PaymentHydrator | `src/Billing/Payment/Infrastructure/Hydrators/PaymentHydrator.php` |

```php
final class PaymentHydrator {
    public function hydrate(PaymentModel $model): Payment {
        return new Payment(
            id:               PaymentId::fromString($model->{PaymentTable::ID}),
            memberId:         MemberId::fromString($model->{PaymentTable::MEMBER_ID}),
            membershipPlanId: MembershipPlanId::fromString($model->{PaymentTable::MEMBERSHIP_PLAN_ID}),
            recordedBy:       UserId::fromString($model->{PaymentTable::RECORDED_BY}),
            amountCents:      (int) $model->{PaymentTable::AMOUNT_CENTS},
            paymentDate:      new \DateTimeImmutable($model->{PaymentTable::PAYMENT_DATE}),
            billingMonth:     $model->{PaymentTable::BILLING_MONTH},
            notes:            $model->{PaymentTable::NOTES},
            createdAt:        new \DateTimeImmutable((string) $model->{PaymentTable::CREATED_AT}),
        );
    }

    public function dehydrate(Payment $payment): array {
        return [
            PaymentTable::ID               => $payment->id->value(),
            PaymentTable::MEMBER_ID        => $payment->memberId->value(),
            PaymentTable::MEMBERSHIP_PLAN_ID => $payment->membershipPlanId->value(),
            PaymentTable::RECORDED_BY      => $payment->recordedBy->value(),
            PaymentTable::AMOUNT_CENTS     => $payment->amountCents,
            PaymentTable::PAYMENT_DATE     => $payment->paymentDate->format('Y-m-d'),
            PaymentTable::BILLING_MONTH    => $payment->billingMonth,
            PaymentTable::NOTES            => $payment->notes,
        ];
    }
}
```

#### Repository Implementation

| Interface | Implementation | Tables Used |
|-----------|----------------|-------------|
| PaymentRepositoryInterface | `src/Billing/Payment/Infrastructure/Repositories/PaymentRepository.php` | payments, members, users, membership_plans |

**Key implementation notes:**

- `save()` uses `PaymentModel::create()` — payments are immutable, never updated.
- `findOverdueMembers()` uses a single LEFT JOIN query (no N+1):

```sql
-- Overdue members query
SELECT m.id, m.member_number, m.first_name, m.last_name,
       u.email,
       mp.name as plan_name,
       MAX(p_last.payment_date) as last_payment_date
FROM members m
JOIN users u ON u.id = m.user_id AND u.status = 'active'
LEFT JOIN member_plan_assignments mpa
    ON mpa.member_id = m.id
    AND mpa.assigned_at = (
        SELECT MAX(assigned_at) FROM member_plan_assignments
        WHERE member_id = m.id
    )
LEFT JOIN membership_plans mp ON mp.id = mpa.membership_plan_id
LEFT JOIN payments p_last ON p_last.member_id = m.id
WHERE m.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM payments p
      WHERE p.member_id = m.id
        AND p.billing_month = ?   -- current YYYY-MM
  )
GROUP BY m.id, m.member_number, m.first_name, m.last_name, u.email, mp.name
ORDER BY m.member_number ASC
```

- `findAll()` uses a single JOIN query with optional WHERE clauses — no N+1.
- `findByMemberId()` uses a single JOIN with membership_plans to include plan name — no N+1.

---

### 3. Application Layer — `backend/src/Billing/Payment/Application/`

#### Commands

| Command | Handler | File Path | Returns |
|---------|---------|-----------|---------|
| RecordPaymentCommand | RecordPaymentHandler | `Application/Commands/RecordPayment/` | void |

```php
// Command
final class RecordPaymentCommand {
    public function __construct(
        public readonly PaymentId        $paymentId,
        public readonly MemberId         $memberId,
        public readonly MembershipPlanId $membershipPlanId,
        public readonly UserId           $recordedBy,
        public readonly int              $amountCents,
        public readonly \DateTimeImmutable $paymentDate,
        public readonly ?string          $notes = null,
    ) {}
}

// Handler (returns void)
// 1. Derive billing_month = paymentDate->format('Y-m')
// 2. Check no existing payment for member+billing_month via:
//    PaymentRepositoryInterface::findByMemberAndBillingMonth()
//    — throws PaymentAlreadyExistsForMonthException if found
// 3. Verify member exists: MemberRepositoryInterface::getById(memberId)
//    — throws MemberNotFoundException if not found
// 4. Verify plan exists: MembershipPlanRepositoryInterface::getById(planId)
//    — throws MembershipPlanNotFoundException if not found
// 5. Create Payment entity via Payment::create() — throws InvalidPaymentAmountException if amount <= 0
// 6. Save via PaymentRepositoryInterface::save(payment)
```

**Note:** Steps 3 and 4 (member and plan verification) use cross-BC repository interfaces. Since Actions already inject MemberRepositoryInterface in the members epic, the same interfaces are injected into RecordPaymentHandler. No query bus needed.

#### Queries

| Query | Handler | File Path | Returns |
|-------|---------|-----------|---------|
| GetPaymentByIdQuery | GetPaymentByIdHandler | `Application/Queries/GetPaymentById/` | PaymentDetailRM |
| ListPaymentsQuery | ListPaymentsHandler | `Application/Queries/ListPayments/` | array{items: PaymentListItemRM[], total: int} |
| GetOverdueMembersQuery | GetOverdueMembersHandler | `Application/Queries/GetOverdueMembers/` | OverdueMemberRM[] |
| GetMyPaymentsQuery | GetMyPaymentsHandler | `Application/Queries/GetMyPayments/` | MemberPaymentListItemRM[] |

```php
// ListPaymentsQuery
final class ListPaymentsQuery {
    public function __construct(
        public readonly ?string $memberId = null,
        public readonly ?int    $year     = null,
        public readonly ?int    $month    = null,
        public readonly int     $page     = 1,
        public readonly int     $perPage  = 20,
    ) {}
}

// GetOverdueMembersQuery — no parameters; uses server date
final class GetOverdueMembersQuery {}

// GetMyPaymentsQuery — for authenticated member
final class GetMyPaymentsQuery {
    public function __construct(public readonly MemberId $memberId) {}
}
```

**GetOverdueMembersHandler detail:**

```php
// Handler
// 1. Determine current billing_month = (new \DateTimeImmutable())->format('Y-m')
// 2. Call PaymentRepositoryInterface::findOverdueMembers(billingMonth)
// 3. Return OverdueMemberRM[]
```

---

### 4. HTTP Layer — `backend/app/Http/Actions/Payments/`

#### Folder Structure

```
app/Http/Actions/Payments/
├── Record/
│   ├── RecordPaymentAction.php
│   ├── RecordPaymentRequest.php
│   └── RecordPaymentDto.php
├── List/
│   ├── ListPaymentsAction.php
│   ├── ListPaymentsRequest.php
│   └── ListPaymentsDto.php
├── Detail/
│   └── GetPaymentDetailAction.php
└── Overdue/
    └── GetOverdueMembersAction.php

app/Http/Actions/MemberPayments/
└── GetMyPaymentsAction.php

app/Http/Actions/Payments/Shared/
├── PaymentResource.php
├── PaymentListResource.php
├── OverdueMemberResource.php
└── MemberPaymentListResource.php
```

#### Actions

```php
// RecordPaymentAction
final class RecordPaymentAction {
    public function __construct(
        private readonly RecordPaymentHandler  $handler,
        private readonly GetPaymentByIdHandler $query,
    ) {}

    public function __invoke(RecordPaymentRequest $request): JsonResponse {
        $dto       = $request->getDto();
        $paymentId = PaymentId::random();
        $adminId   = UserId::fromString($request->user()->id);

        try {
            $this->handler->handle(new RecordPaymentCommand(
                paymentId:        $paymentId,
                memberId:         MemberId::fromString($dto->memberId),
                membershipPlanId: MembershipPlanId::fromString($dto->membershipPlanId),
                recordedBy:       $adminId,
                amountCents:      $dto->amountCents,
                paymentDate:      $dto->paymentDate,
                notes:            $dto->notes,
            ));
        } catch (MemberNotFoundException) {
            return response()->json(['error' => 'Socio no encontrado', 'code' => 'MEMBER_NOT_FOUND'], 404);
        } catch (MembershipPlanNotFoundException) {
            return response()->json(['error' => 'Plan de membresia no encontrado', 'code' => 'MEMBERSHIP_PLAN_NOT_FOUND'], 422);
        } catch (PaymentAlreadyExistsForMonthException) {
            return response()->json(['error' => 'Ya existe un pago registrado para este socio en este mes', 'code' => 'PAYMENT_ALREADY_EXISTS_FOR_MONTH'], 409);
        } catch (InvalidPaymentAmountException) {
            return response()->json(['error' => 'El importe debe ser mayor que cero', 'code' => 'INVALID_PAYMENT_AMOUNT'], 422);
        }

        $rm = $this->query->handle(new GetPaymentByIdQuery($paymentId));
        return (new PaymentResource($rm))->toResponse(201);
    }
}
```

```php
// RecordPaymentRequest — no framework validation, only getDto()
public function getDto(): RecordPaymentDto {
    return new RecordPaymentDto(
        memberId:         (string) $this->input('member_id', ''),
        membershipPlanId: (string) $this->input('membership_plan_id', ''),
        amountCents:      (int) $this->input('amount_cents', 0),
        paymentDate:      new \DateTimeImmutable((string) $this->input('payment_date', 'today')),
        notes:            $this->input('notes') ? (string) $this->input('notes') : null,
    );
}
```

```php
// GetOverdueMembersAction
final class GetOverdueMembersAction {
    public function __construct(private readonly GetOverdueMembersHandler $handler) {}

    public function __invoke(): JsonResponse {
        $items = $this->handler->handle(new GetOverdueMembersQuery());
        return (new OverdueMemberResource($items))->toResponse();
    }
}
```

```php
// GetMyPaymentsAction — for authenticated member
final class GetMyPaymentsAction {
    public function __construct(
        private readonly GetMyPaymentsHandler        $handler,
        private readonly MemberRepositoryInterface   $memberRepository,
    ) {}

    public function __invoke(Request $request): JsonResponse {
        $userId = UserId::fromString($request->user()->id);
        $member = $this->memberRepository->findByUserId($userId);

        if ($member === null) {
            return response()->json(['error' => 'Perfil no encontrado', 'code' => 'MEMBER_NOT_FOUND'], 404);
        }

        $items = $this->handler->handle(new GetMyPaymentsQuery($member->id));
        return (new MemberPaymentListResource($items))->toResponse();
    }
}
```

#### Resources

```php
// PaymentResource — single payment detail
final class PaymentResource {
    public function __construct(private readonly PaymentDetailRM $rm) {}
    public function toResponse(int $status = 200): JsonResponse {
        return response()->json([
            'id'             => $this->rm->id,
            'member_id'      => $this->rm->memberId,
            'member_number'  => $this->rm->memberNumber,
            'member_name'    => $this->rm->memberFirstName . ' ' . $this->rm->memberLastName,
            'plan'           => ['id' => $this->rm->membershipPlanId, 'name' => $this->rm->planName],
            'amount_cents'   => $this->rm->amountCents,
            'payment_date'   => $this->rm->paymentDate,
            'billing_month'  => $this->rm->billingMonth,
            'notes'          => $this->rm->notes,
            'recorded_by'    => $this->rm->recordedBy,
            'created_at'     => $this->rm->createdAt,
        ], $status);
    }
}

// OverdueMemberResource — wraps OverdueMemberRM[]
final class OverdueMemberResource {
    /** @param OverdueMemberRM[] $items */
    public function __construct(private readonly array $items) {}
    public function toResponse(): JsonResponse {
        return response()->json([
            'data' => array_map(fn($item) => [
                'member_id'         => $item->memberId,
                'member_number'     => $item->memberNumber,
                'first_name'        => $item->firstName,
                'last_name'         => $item->lastName,
                'email'             => $item->email,
                'plan_name'         => $item->planName,
                'last_payment_date' => $item->lastPaymentDate,
            ], $this->items),
            'meta' => ['total' => count($this->items)],
        ]);
    }
}
```

#### Endpoints

| Method | Route | Action | Middleware | Description |
|--------|-------|--------|------------|-------------|
| POST | `/api/admin/payments` | RecordPaymentAction | `auth:sanctum`, `role.admin` | Record a cash payment |
| GET | `/api/admin/payments` | ListPaymentsAction | `auth:sanctum`, `role.admin` | List payments (paginated + filters) |
| GET | `/api/admin/payments/overdue` | GetOverdueMembersAction | `auth:sanctum`, `role.admin` | List overdue members |
| GET | `/api/admin/payments/{id}` | GetPaymentDetailAction | `auth:sanctum`, `role.admin` | Payment detail |
| GET | `/api/member/payments` | GetMyPaymentsAction | `auth:sanctum`, `role.member` | Member views own payments |

**Route ordering note:** `/api/admin/payments/overdue` must be registered BEFORE `/api/admin/payments/{id}` to prevent Laravel routing the literal string "overdue" as an ID.

---

### 5. Service Provider / Bindings

Add to `AppServiceProvider`:

```php
$this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
```

---

### 6. Collateral Changes

| File | Change Type | Description |
|------|-------------|-------------|
| `routes/api.php` | Extension | Add admin/payments and member/payments route groups |
| `app/Providers/AppServiceProvider.php` | Extension | Bind PaymentRepositoryInterface |
| `src/Core/Member/Domain/Repositories/MemberRepositoryInterface.php` | **No change needed** | `findByUserId()` already exists and is used by GetMyPaymentsAction |

**No breaking changes.** All additions are additive.

---

## Database Schema

### `payments` (new)

```sql
CREATE TABLE payments (
    id                  CHAR(26)    NOT NULL PRIMARY KEY,
    member_id           CHAR(26)    NOT NULL,
    membership_plan_id  CHAR(26)    NOT NULL,
    recorded_by         CHAR(26)    NOT NULL,
    amount_cents        INT         NOT NULL,
    payment_date        DATE        NOT NULL,
    billing_month       CHAR(7)     NOT NULL,
    notes               TEXT        NULL,
    created_at          TIMESTAMP   NULL,
    updated_at          TIMESTAMP   NULL,
    CONSTRAINT fk_payments_member    FOREIGN KEY (member_id)          REFERENCES members(id),
    CONSTRAINT fk_payments_plan      FOREIGN KEY (membership_plan_id) REFERENCES membership_plans(id),
    CONSTRAINT fk_payments_admin     FOREIGN KEY (recorded_by)        REFERENCES users(id),
    CONSTRAINT uq_member_billing     UNIQUE (member_id, billing_month),
    INDEX idx_billing_month          (billing_month),
    INDEX idx_member_id              (member_id),
    INDEX idx_payment_date           (payment_date)
);
```

---

## API Contract (Response Shapes)

### `POST /api/admin/payments` → 201

```json
{
  "id": "01J4...",
  "member_id": "01J3...",
  "member_number": 1,
  "member_name": "Carlos Ruiz",
  "plan": { "id": "01J2...", "name": "4-5 Dias" },
  "amount_cents": 4000,
  "payment_date": "2026-06-05",
  "billing_month": "2026-06",
  "notes": null,
  "recorded_by": "01J1...",
  "created_at": "2026-06-11T10:00:00+00:00"
}
```

### `GET /api/admin/payments/overdue` → 200

```json
{
  "data": [
    {
      "member_id": "01J3...",
      "member_number": 2,
      "first_name": "Ana",
      "last_name": "Garcia",
      "email": "ana@example.com",
      "plan_name": "3 Dias",
      "last_payment_date": "2026-05-03"
    }
  ],
  "meta": { "total": 1 }
}
```

### `GET /api/member/payments` → 200

```json
{
  "data": [
    {
      "id": "01J4...",
      "amount_cents": 4000,
      "payment_date": "2026-06-05",
      "billing_month": "2026-06",
      "plan_name": "4-5 Dias"
    }
  ]
}
```

---

## Dependencies

| Dependency | Type | Status |
|------------|------|--------|
| `Core/Member/MemberId` | Cross-BC value object | Defined in epic-members |
| `Core/Member/MembershipPlanId` | Cross-BC value object | Defined in epic-members |
| `Core/Member/MemberRepositoryInterface` | Cross-BC interface (findByUserId) | Defined in epic-members |
| `Core/Member/MembershipPlanRepositoryInterface` | Cross-BC interface (getById) | Defined in epic-members |
| `Shared/Auth/UserId` | Shared value object | Already exists |
| `symfony/uid` (Ulid) | PHP package | Already installed |
| `members` table | DB | Already exists |
| `membership_plans` table | DB | Already exists + seeded |
| `member_plan_assignments` table | DB | Created in epic-members |

---

## Testing Strategy

| Test Type | Scope | Priority | File Path |
|-----------|-------|----------|-----------|
| Unit | Payment entity — create(), billing_month derivation, invalid amount | High | `tests/Unit/Billing/Payment/PaymentTest.php` |
| Unit | RecordPaymentHandler — duplicate month check, member/plan validation | High | `tests/Unit/Billing/Payment/RecordPaymentHandlerTest.php` |
| Unit | GetOverdueMembersHandler — current month logic | High | `tests/Unit/Billing/Payment/GetOverdueMembersHandlerTest.php` |
| Integration | `POST /api/admin/payments` — success, duplicate month (409), bad member (404), bad amount (422) | High | `tests/Feature/Payments/RecordPaymentTest.php` |
| Integration | `GET /api/admin/payments` — filters by member, year, month | High | `tests/Feature/Payments/ListPaymentsTest.php` |
| Integration | `GET /api/admin/payments/overdue` — accuracy with and without payments | High | `tests/Feature/Payments/OverdueMembersTest.php` |
| Integration | `GET /api/admin/payments/{id}` — success + not found | Medium | `tests/Feature/Payments/GetPaymentDetailTest.php` |
| Integration | `GET /api/member/payments` — own only, role enforcement | High | `tests/Feature/Payments/MyPaymentsTest.php` |

---

## Implementation Order

1. [ ] Domain: `PaymentId` Value Object
2. [ ] Domain: `Payment` Entity with `create()` (includes billing_month derivation)
3. [ ] Domain: `PaymentNotFoundException`, `PaymentAlreadyExistsForMonthException`, `InvalidPaymentAmountException`
4. [ ] Domain: `PaymentDetailRM`, `PaymentListItemRM`, `OverdueMemberRM`, `MemberPaymentListItemRM` ReadModels
5. [ ] Domain: `PaymentRepositoryInterface`
6. [ ] Infrastructure: Migration — `create_payments_table`
7. [ ] Infrastructure: `PaymentTable` constants class
8. [ ] Infrastructure: `PaymentModel`
9. [ ] Infrastructure: `PaymentHydrator`
10. [ ] Infrastructure: `PaymentRepository` implementation
11. [ ] Application: `RecordPaymentCommand` + `RecordPaymentHandler`
12. [ ] Application: `GetPaymentByIdQuery` + `GetPaymentByIdHandler`
13. [ ] Application: `ListPaymentsQuery` + `ListPaymentsHandler`
14. [ ] Application: `GetOverdueMembersQuery` + `GetOverdueMembersHandler`
15. [ ] Application: `GetMyPaymentsQuery` + `GetMyPaymentsHandler`
16. [ ] HTTP: DTOs (`RecordPaymentDto`, `ListPaymentsDto`)
17. [ ] HTTP: Requests (`RecordPaymentRequest`, `ListPaymentsRequest`)
18. [ ] HTTP: Resources (`PaymentResource`, `PaymentListResource`, `OverdueMemberResource`, `MemberPaymentListResource`)
19. [ ] HTTP: Actions (all 5)
20. [ ] HTTP: Routes in `api.php` (overdue route registered BEFORE `{id}` route)
21. [ ] Bindings in `AppServiceProvider`
22. [ ] Tests: Unit suite
23. [ ] Tests: Feature suite

---

## Open Technical Questions

None — all decisions resolved.

---

## Risks

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Overdue query slow if many members | Low | Low | ~100 members max in single gym; indexes on billing_month and member_id suffice |
| Race condition: two admins record payment for same member+month simultaneously | Very Low | Low | DB unique constraint on (member_id, billing_month) provides hard guarantee at DB level |
| Cross-BC dependency coupling (Payment references Member interfaces) | Low | Low | Acceptable in single-service app; Payment only reads, never writes Member data |
| Route collision: `overdue` vs `{id}` | Low | Medium | Explicitly documented — overdue route registered first in api.php |
