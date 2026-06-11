# HTTP Layer - Actions (CRITICAL)

**Read before creating or modifying any Action**

## Executive Summary

**Actions MUST be thin. NEVER use direct database access, loops, validations, or business logic in Actions.**

All that goes in **Handlers** (Application layer) and **Repositories** (Infrastructure layer).

---

## What is an Action?

An **Action** is the HTTP entry point in hexagonal architecture. Its only responsibility is to **orchestrate** the call between the HTTP layer and the Application layer.

### Location

```
Apps/Api/
├── Order/
│   ├── Store/
│   │   ├── StoreOrderAction        # HTTP orchestration
│   │   ├── StoreOrderRequest       # Parse & validate
│   │   └── StoreOrderDto           # Transfer object
│   └── OrderController             # Injects Actions
```

---

## The Golden Rule: THIN Actions

**An Action has exactly 3 responsibilities (and ONLY 3):**

1. **Verify access** - Call `verifyAccess()` with auth token
2. **Dispatch Command/Query** - Delegate to Application layer via CommandBus/QueryBus
3. **Return response** - Use `ResService` to convert DTO → Res

**Anything else does NOT belong in the Action.**

---

## FORBIDDEN in Actions

### 0. CRITICAL: DO NOT return JSON directly, arrays, or DTOs

**NEVER return these types from Actions:**

```
// FORBIDDEN
function invoke(...): JsonResponse
    return new JsonResponse([...])

// FORBIDDEN
function invoke(...): array
    return ['id': id, 'name': name]

// FORBIDDEN
function invoke(...): OrderDto
    return dto
```

**ALWAYS return Resource (XxxRes):**

```
// CORRECT
function invoke(...): OrderRes
    commandBus.dispatch(new CreateOrderCommand(...))
    return resService.getOrderResource(orderId)
```

**Why is it wrong?**
- Actions must be decoupled from HTTP layer
- Not testable without HTTP context
- Not reusable from CLI/Queue
- Violates separation of responsibilities

**Where should it go?**
- **Action**: Returns `XxxRes` via `ResService`
- **Controller**: Converts `Res` to JsonResponse with `response.json(resource)`
- **ResService**: Located in `Apps/Api/{Module}/Shared/Services/XxxResService`
- **Resource**: Located in `Apps/Api/{Module}/Shared/XxxRes`, implements `JsonSerializable`

### 1. DO NOT use direct database access

```
// INCORRECT
function invoke(AddItemsDto dto): JsonResponse
    // NEVER do this in Action
    db.table('order_items')
        .where('order_id', dto.id)
        .insert([...])

    exists = db.table('order_items')
        .where('email', email)
        .exists()
```

**Why is it wrong?**
- Violates hexagonal architecture (HTTP layer accessing data)
- Not testable
- Not reusable (what if you need it from CLI?)
- Mixes responsibilities

**Where should it go?**
- In **Repository** (`addItems()`, `checkExists()`)

---

### 2. DO NOT put business logic

```
// INCORRECT
function invoke(CreateOrderDto dto): JsonResponse
    // Validations
    if !dto.email && !dto.phone:
        throw ValidationException('Email or phone required')

    // Calculations
    total = dto.price * dto.quantity
    tax = total * 0.21
    finalTotal = total + tax

    // Loops
    for item in dto.items:
        // processing...

    // Transformations
    normalizedEmail = dto.email.toLowerCase().trim()
```

**Why is it wrong?**
- Business logic does NOT belong in HTTP layer
- Not unit testable
- Hard to maintain
- Violates Single Responsibility Principle

**Where should it go?**
- In **Handler** (Application layer)
- In **Entity** (Domain layer) if it's a domain rule
- In **Domain Service** if it involves multiple entities

---

### 3. DO NOT access Models directly

```
// INCORRECT
function invoke(UpdateOrderDto dto): JsonResponse
    order = OrderModel.find(dto.id)
    order.update({
        'status': 'active',
        'name': dto.name
    })
    order.save()
```

**Why is it wrong?**
- HTTP layer accessing Infrastructure directly
- Bypassing domain logic and events
- Not using CQRS
- Not testable without DB

**Where should it go?**
- Dispatch **UpdateOrderCommand**
- Handler gets entity via **Repository**
- Calls domain method `order.update(...)`
- Repository persists

---

### 4. DO NOT transform data

```
// INCORRECT
function invoke(ImportItemsDto dto): JsonResponse
    transformed = dto.items.map(item =>
        {
            'name': item.name.toUpperCase(),
            'email': item.email.toLowerCase().trim(),
            'phone': item.phone.replace(/[^0-9]/g, '')
        }
    )

    // ... do something with transformed
```

**Why is it wrong?**
- Transformations are business logic
- Not reusable
- Makes testing difficult

**Where should it go?**
- In **Handler** if it's application transformation
- In **ValueObject** if it's domain normalization
- In **Domain Service** if it's complex

---

## CORRECT: Thin Action

```
class AddOrderItemsAction:
    constructor(
        commandBus: CommandBusInterface,
        queryBus: QueryBusInterface,
        resService: OrderResService,
        securityService: SecurityServiceInterface
    )

    function invoke(
        dto: AddOrderItemsDto,
        authPayload: AuthPayload
    ): OrderRes
        // 1. Verify access (security concern)
        order = queryBus.query(
            new GetOrderByIdQuery(dto.id, dto.app)
        )
        verifyAccess(
            authPayload,
            dto.app,
            order.groupId,
            order.organizationId
        )

        // 2. Dispatch command (delegation to Application layer)
        commandBus.dispatch(
            new AddItemsToOrderCommand(
                orderId: dto.id,
                app: dto.app,
                items: dto.items
            )
        )

        // 3. Return Resource (NOT JsonResponse)
        // Controller will be responsible for converting to JsonResponse
        return resService.getOrderResource(
            dto.id,
            dto.app
        )
```

**Characteristics of a correct Action:**
- 15-20 lines of code
- No direct database access
- No `foreach` or `map`
- No complex `if` (only null checks)
- Dispatch to ONE Command/Query
- **CRITICAL: Returns Resource (`XxxRes`) via ResService** - NOT JsonResponse, NOT arrays, NOT DTOs
- All logic is in Handler
- Controller handles JsonResponse using `response.json(resource)`

---

## Distribution of Responsibilities

| What | Where | Why |
|-----|-------|---------|
| Verify auth and permissions | Action | HTTP/Security concern |
| Generate IDs (creation) | Action | Before creating command |
| Validate email/phone required | Handler | Business rule |
| Check duplicates | Repository | Data access |
| Loop over items | Handler | Processing |
| Insert to database | Repository | Data access |
| Update totals | Handler + Entity | Domain logic |
| Publish events | Handler | Application orchestration |
| Convert DTO → Res | ResService | HTTP serialization |

---

## Real Project Example

### BEFORE - Incorrect Action (58 lines)

```
class AddOrderItemsAction:
    function invoke(AddOrderItemsDto dto): OrderRes
        // Verify access (OK)
        order = queryBus.query(new GetOrderByIdQuery(dto.id, dto.app))
        verifyAccess(authPayload, dto.app, order.groupId, order.organizationId)

        // WRONG: Logic in Action
        itemType = order.groupId !== null ? 'GroupItem' : 'OrganizationItem'
        now = timestamp()

        // WRONG: Loop in Action
        for itemData in dto.items:
            name = itemData['name']
            email = itemData['email'] ?? null
            phone = itemData['phone'] ?? null
            countryCode = itemData['country_code'] ?? null
            acceptsTerms = itemData['accepts_terms'] ?? true

            // WRONG: Validation in Action
            if !email && !phone:
                continue

            // WRONG: Direct database access in Action
            exists = db.table('order_items')
                .where('order_id', dto.id.value())
                .where(query =>
                    if email:
                        query.orWhere('email', email)
                    if phone:
                        query.orWhere('phone', phone)
                )
                .exists()

            // WRONG: More logic
            if exists:
                continue

            // WRONG: Direct insert
            db.table('order_items').insert({
                'order_id': dto.id.value(),
                'item_id': null,
                'item_type': itemType,
                'name': name,
                'email': email,
                'phone': phone,
                'country_code': countryCode,
                'accepts_terms': acceptsTerms,
                'created_at': now
            })

        return orderResService.getOrderResource(dto.id, dto.app)
```

**Problems:**
1. Uses direct database access (lines 28, 47)
2. Has `foreach` loop (line 17)
3. Validations in Action (line 26)
4. Business logic (determine itemType, line 13)
5. 58 lines of code
6. Injects `CommandBusInterface` but never uses it
7. Not testable without database
8. Not reusable from CLI/Queue

---

### AFTER - Correct Action (15 lines)

```
class AddOrderItemsAction:
    constructor(
        commandBus: CommandBusInterface,
        queryBus: QueryBusInterface,
        resService: OrderResService,
        securityService: SecurityServiceInterface
    )

    function invoke(
        dto: AddOrderItemsDto,
        authPayload: AuthPayload
    ): OrderRes
        // 1. Verify access
        order = queryBus.query(new GetOrderByIdQuery(dto.id, dto.app))
        verifyAccess(authPayload, dto.app, order.groupId, order.organizationId)

        // 2. Dispatch command (all logic goes to Handler)
        commandBus.dispatch(
            new AddItemsToOrderCommand(
                orderId: dto.id,
                app: dto.app,
                items: dto.items
            )
        )

        // 3. Return response
        return resService.getOrderResource(dto.id, dto.app)
```

**Improvements:**
1. 15 lines of code
2. No direct database access
3. No loops or validations
4. Uses `CommandBusInterface` correctly
5. Testable without database
6. Reusable (Command can be called from CLI/Queue)
7. All logic in `AddItemsToOrderHandler`

---

### Logic moved to correct layers:

#### `AddItemsToOrderCommand` (Application)
```
class AddItemsToOrderCommand implements CommandInterface:
    // @param array<{name: string, email: ?string, phone: ?string, country_code: ?string, accepts_terms: bool}> items
    constructor(
        orderId: OrderId,
        app: AppEnum,
        items: array
    )
```

#### `AddItemsToOrderHandler` (Application)
```
class AddItemsToOrderHandler implements CommandHandlerInterface:
    constructor(
        repository: OrderRepositoryInterface,
        eventBus: EventBusInterface
    )

    function invoke(command: AddItemsToOrderCommand): void
        order = repository.getById(command.orderId, command.app)
        itemType = order.getItemType()

        // Validate and filter items
        validItems = []
        for item in command.items:
            // Validation: email or phone required
            if empty(item['email']) && empty(item['phone']):
                continue

            validItems.append({
                'name': item['name'],
                'email': item['email'] ?? null,
                'phone': item['phone'] ?? null,
                'country_code': item['country_code'] ?? null,
                'accepts_terms': item['accepts_terms']
            })

        if empty(validItems):
            return

        // Delegate to Repository for insertion with duplicate checking
        repository.addItems(command.orderId, validItems, itemType)

        // Update totals
        newCount = repository.countItems(command.orderId)
        order.updateItemCount(newCount)

        // Persist and publish events
        repository.save(order)
        eventBus.publishEvents(order.releaseEvents())
```

#### `OrderRepository.addItems()` (Infrastructure)
```
function addItems(orderId: OrderId, items: array, itemType: string): void
    now = timestamp()

    for item in items:
        email = item['email'] ?? null
        phone = item['phone'] ?? null

        // Duplicate checking
        exists = OrderItemModel.query()
            .where('order_id', orderId.value())
            .where(query =>
                if email:
                    query.orWhere('email', email)
                if phone:
                    query.orWhere('phone', phone)
            )
            .exists()

        if exists:
            continue

        // Insertion
        OrderItemModel.query().insert({
            'order_id': orderId.value(),
            'item_id': null,
            'item_type': itemType,
            'name': item['name'],
            'email': email,
            'phone': phone,
            'country_code': item['country_code'] ?? null,
            'accepts_terms': item['accepts_terms'],
            'created_at': now
        })
```

---

## Benefits of Thin Actions

### 1. Testability
```
// Action test - No DB, just verify correct dispatch
function test_dispatches_add_items_command(): void
    commandBus = mock(CommandBusInterface)
    commandBus.shouldReceive('dispatch')
        .once()
        .with(instanceOf(AddItemsToOrderCommand))

    action = new AddOrderItemsAction(commandBus, ...)
    action.invoke(dto, authPayload)
```

### 2. Reusability
```
// Now you can use the Command from CLI
cli order:add-items {orderId} --items=file.csv

// Or from Queue
dispatch(new AddItemsToOrderCommand(orderId, app, items))
```

### 3. Maintainability
- Business logic in ONE place (Handler)
- Changes to business rules do NOT touch HTTP layer
- Easy to understand (3 clear responsibilities)

### 4. Separation of Concerns
- HTTP layer handles HTTP
- Application layer handles orchestration
- Domain layer handles business rules
- Infrastructure layer handles data

### 5. DDD Compliance
- Correct hexagonal architecture
- Well-defined ports and adapters
- Domain independent of HTTP

---

## Checklist Before Committing Action

Before committing any Action, verify:

- [ ] **Action has <= 20 lines of code**
- [ ] **Does NOT use direct database access**
- [ ] **Does NOT have `foreach`, `map`, `filter`** or other loops
- [ ] **Does NOT have complex `if`** (only null checks allowed)
- [ ] **Dispatches exactly ONE Command or Query**
- [ ] **CRITICAL: Does NOT return `JsonResponse`** - Actions must be decoupled from HTTP
- [ ] **Returns Resource (`XxxRes`) via ResService** - NOT arrays, NOT DTOs, NOT JsonResponse
- [ ] **All logic is in Handler or Domain**
- [ ] **If it injects CommandBusInterface, it uses it**
- [ ] **Maximum 3 responsibilities:** verify access, dispatch, return Res
- [ ] **Controller handles JsonResponse** - Controller converts Res to JsonResponse

If any answer is NO, refactor before committing.

---

## Frequently Asked Questions

### Can I do an if to check null?

Yes, simple null checks are OK:
```
if dto.groupId === null:
    throw InvalidArgumentException('groupId required')
```

No, complex ifs are NOT OK:
```
if !email && !phone:
    // complex validation -> goes to Handler
```

### Can I generate IDs in Action?

Yes, for creation operations:
```
orderId = OrderId.random()
commandBus.dispatch(new CreateOrderCommand(orderId, ...))
```

### Can I do two dispatches in one Action?

Avoid it. If you need two Commands, you probably need:
- A ProcessManager (for complex workflows)
- Or a Handler that dispatches the second Command

### Can I call a Repository from Action?

NO. Always use QueryBus/CommandBus.

Exception: The `verifyAccess` trait may need QueryBus to verify ownership, but that's an HTTP security concern, not business.

---

## See Also

- [architecture.md](architecture.md) - DDD and Hexagonal Architecture
- [application-layer.md](application-layer.md) - Commands, Queries, Handlers
- [critical-rules.md](critical-rules.md) - Critical project rules
- [http-requests-pattern.md](http-requests-pattern.md) - HTTP Requests pattern
