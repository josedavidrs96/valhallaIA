# Development Workflow

**Strict implementation order for features in 3 phases**

---

## Frontend-Backend Features?

If your feature involves both frontend and backend:
1. **Read [Frontend-Backend Integration](frontend-backend-integration.md) FIRST**
2. **Define API contract before coding**
3. **Choose development strategy** (Backend-first, Frontend-first, or Parallel)

This document covers **backend implementation only**. For full-stack features, combine this with the frontend-backend integration guide.

---

## Workflow Summary

```
PHASE 1: Domain Layer
  ├── Entities (with create, update, transition methods)
  ├── Value Objects
  └── Enums
  CONFIRM before continuing

PHASE 2: Infrastructure Layer
  ├── Repository Interface (Domain)
  ├── Repository Implementation (Infrastructure)
  ├── Mapper/Hydrator
  ├── ORM Model
  └── Migrations
  CONFIRM before continuing

PHASE 3: Application + HTTP Layer
  ├── Request classes
  ├── Action classes
  ├── Commands (write - void)
  ├── Queries (read - DTOs)
  ├── Controller
  ├── Resources
  └── Routes
  CONFIRM before continuing
```

---

## PHASE 1: Domain Layer

### 1.1 Create Entities

**Important rules:**

#### Public Properties
```
// CORRECT
readonly id: OrderId
groupId: ?GroupId
name: string
status: OrderStatus

// INCORRECT - DO NOT use getters/setters
private name: string
function getName(): string { return this.name }
function setName(name: string): void { this.name = name }
```

#### Static Factory Method: `create()`
```
static function create(
    id: OrderId,
    groupId: ?GroupId,
    name: string
): Order
    // Validations
    if groupId === null && organizationId === null:
        throw OrderMustHaveOwnerException()

    instance = new Order(
        id: id,
        groupId: groupId,
        name: name,
        status: OrderStatus.DRAFT
    )

    // Record domain event
    instance.recordLast(new OrderCreatedEvent(id))

    return instance
```

#### `update()` Method
```
function update(
    name: string,
    description: ?string = null
): void
    // Validate that it can be updated
    if this.status === OrderStatus.PROCESSING:
        throw OrderCannotBeEditedException()

    this.name = name
    if description !== null:
        this.description = description

    this.recordLast(new OrderUpdatedEvent(this.id))
```

#### Specific Methods for State Transitions

**NEVER change status directly**

```
// INCORRECT
order.status = OrderStatus.SCHEDULED

// CORRECT - Specific method
function schedule(scheduledAt: DateTime): void
    // Validate pre-conditions
    if this.status !== OrderStatus.DRAFT:
        throw OrderCannotBeScheduledException(
            "Only DRAFT orders can be scheduled"
        )

    if scheduledAt <= now():
        throw InvalidScheduleDateException("Date must be in the future")

    // State transition
    this.status = OrderStatus.SCHEDULED
    this.scheduledAt = scheduledAt

    // Domain event
    this.recordLast(new OrderScheduledEvent(this.id, scheduledAt))

function start(): void
    if this.status !== OrderStatus.SCHEDULED:
        throw OrderCannotBeStartedException()

    this.status = OrderStatus.PROCESSING
    this.startedAt = now()
    this.recordLast(new OrderStartedEvent(this.id))

function complete(): void
    if this.status !== OrderStatus.PROCESSING:
        throw OrderCannotBeCompletedException()

    this.status = OrderStatus.COMPLETED
    this.recordLast(new OrderCompletedEvent(this.id))

function cancel(): void
    if this.status === OrderStatus.COMPLETED:
        throw OrderCannotBeCancelledException()

    this.status = OrderStatus.CANCELLED
    this.recordLast(new OrderCancelledEvent(this.id))
```

#### Complete Entity Example

```
class Order extends BaseEntity:
    // Private constructor
    private constructor(
        readonly id: OrderId,
        groupId: ?GroupId,
        organizationId: ?OrganizationId,
        name: string,
        description: ?string,
        status: OrderStatus,
        listId: ListId,
        templateId: TemplateId,
        scheduledAt: ?DateTime,
        startedAt: ?DateTime
    )

    // Factory method
    static function create(
        id: OrderId,
        groupId: ?GroupId,
        organizationId: ?OrganizationId,
        name: string,
        listId: ListId,
        templateId: TemplateId
    ): Order
        // Validate ownership
        if groupId === null && organizationId === null:
            throw OrderMustHaveOwnerException()
        if groupId !== null && organizationId !== null:
            throw OrderCannotHaveBothOwnersException()

        instance = new Order(
            id: id,
            groupId: groupId,
            organizationId: organizationId,
            name: name,
            description: null,
            status: OrderStatus.DRAFT,
            listId: listId,
            templateId: templateId,
            scheduledAt: null,
            startedAt: null
        )

        instance.recordLast(OrderCreatedEvent.fromEntity(instance))

        return instance

    // Update
    function update(name: string, description: ?string = null): void
        if this.status !== OrderStatus.DRAFT:
            throw OrderCannotBeEditedException()

        this.name = name
        this.description = description

        this.recordLast(new OrderUpdatedEvent(this.id))

    // State transitions
    function schedule(scheduledAt: DateTime): void
        if this.status !== OrderStatus.DRAFT:
            throw OrderCannotBeScheduledException()

        if scheduledAt <= now():
            throw InvalidScheduleDateException()

        this.status = OrderStatus.SCHEDULED
        this.scheduledAt = scheduledAt

        this.recordLast(new OrderScheduledEvent(this.id, scheduledAt))

    function start(): void
        if this.status !== OrderStatus.SCHEDULED:
            throw OrderCannotBeStartedException()

        this.status = OrderStatus.PROCESSING
        this.startedAt = now()

        this.recordLast(new OrderStartedEvent(this.id))

    function complete(): void
        if this.status !== OrderStatus.PROCESSING:
            throw OrderCannotBeCompletedException()

        this.status = OrderStatus.COMPLETED

        this.recordLast(new OrderCompletedEvent(this.id))

    function cancel(): void
        if this.status === OrderStatus.COMPLETED:
            throw OrderCannotBeCancelledException()

        this.status = OrderStatus.CANCELLED

        this.recordLast(new OrderCancelledEvent(this.id))
```

### 1.2 Create Value Objects

#### IDs (extends Ulid)
```
class OrderId extends Ulid
```

#### Complex Value Objects
```
class OrderCriteria:
    readonly filters: FilterRule[]
    readonly logic: string  // 'AND' | 'OR'

    constructor(filters: FilterRule[], logic: string)
        if logic not in ['AND', 'OR']:
            throw InvalidLogicException()
```

### 1.3 Create Enums

```
enum OrderStatus:
    DRAFT = 'draft'
    SCHEDULED = 'scheduled'
    PROCESSING = 'processing'
    COMPLETED = 'completed'
    CANCELLED = 'cancelled'
```

---

## PHASE 2: Infrastructure Layer

### 2.1 Repository Interface (Domain)

```
interface OrderRepositoryInterface:
    function findById(id: OrderId): ?Order
    function save(order: Order): void
    function delete(id: OrderId): void
```

### 2.2 Repository Implementation (Infrastructure)

```
class OrderRepository implements OrderRepositoryInterface:
    constructor(
        mapper: OrderMapper
    )

    function findById(id: OrderId): ?Order
        model = OrderModel.find(id.value())

        if model === null:
            return null

        return mapper.toDomain(model)

    function save(order: Order): void
        data = mapper.toModel(order)

        OrderModel.updateOrCreate(
            {id: order.id.value()},
            data
        )

    function delete(id: OrderId): void
        OrderModel.where('id', id.value()).delete()
```

### 2.3 Mapper/Hydrator

```
class OrderMapper:
    function toDomain(model: OrderModel): Order
        // Use reflection to instantiate entity with private constructor
        instance = createInstanceWithoutConstructor(Order)

        // Set public properties
        instance.id = OrderId.fromString(model.id)
        instance.groupId = model.group_id ? GroupId.fromString(model.group_id) : null
        instance.status = OrderStatus.from(model.status)
        // ...

        return instance

    function toModel(order: Order): array
        return {
            'id': order.id.value(),
            'group_id': order.groupId?.value(),
            'organization_id': order.organizationId?.value(),
            'name': order.name,
            'status': order.status.value,
            // ...
        }
```

### 2.4 ORM Model

```
class OrderModel extends Model:
    table = 'orders'

    fillable = [
        'id',
        'group_id',
        'organization_id',
        'name',
        'description',
        'status',
        'list_id',
        'template_id',
        'scheduled_at',
        'started_at',
    ]

    casts = {
        'scheduled_at': 'datetime',
        'started_at': 'datetime',
    }

    incrementing = false
    keyType = 'string'
```

### 2.5 Migration

```
Migration CreateOrdersTable:
    function up():
        createTable('orders', table =>
            table.char('id', 26).primary()
            table.char('group_id', 26).nullable()
            table.char('organization_id', 26).nullable()
            table.string('name')
            table.text('description').nullable()
            table.enum('status', ['draft', 'scheduled', 'processing', 'completed', 'cancelled'])
            table.char('list_id', 26)
            table.char('template_id', 26)
            table.timestamp('scheduled_at').nullable()
            table.timestamp('started_at').nullable()
            table.timestamps()

            table.index('group_id')
            table.index('organization_id')
            table.index('status')
        )

        // Constraint: XOR between group_id and organization_id
        addConstraint('orders', 'chk_order_owner',
            '(group_id IS NOT NULL AND organization_id IS NULL) OR
             (group_id IS NULL AND organization_id IS NOT NULL)'
        )

    function down():
        dropTable('orders')
```

---

## PHASE 3: Application + HTTP Layer

### 3.1 Request Class

```
class CreateOrderRequest extends AbstractFormRequest:
    function getDto(): CreateOrderDto
        return new CreateOrderDto(
            groupId: GroupId.fromStringOrNull(this.getStringOrNull('group_id')),
            organizationId: OrganizationId.fromStringOrNull(this.getStringOrNull('organization_id')),
            name: this.getString('name'),
            description: this.getStringOrNull('description'),
            listId: new ListId(this.getString('list_id')),
            templateId: new TemplateId(this.getString('template_id'))
        )
```

### 3.2 Action Class

```
class CreateOrderAction:
    constructor(
        commandBus: CommandBus,
        queryBus: QueryBus,
        resService: OrderResService
    )

    function invoke(dto: CreateOrderDto): OrderRes
        // 1. Generate ID
        id = OrderId.random()

        // 2. Dispatch command (void)
        commandBus.dispatch(
            new CreateOrderCommand(
                id: id,
                groupId: dto.groupId,
                organizationId: dto.organizationId,
                name: dto.name,
                listId: dto.listId,
                templateId: dto.templateId
            )
        )

        // 3. Return Resource via ResService
        return resService.getOrderResource(id)
```

### 3.3 Command (Write Operation)

```
class CreateOrderCommand implements CommandInterface:
    constructor(
        id: OrderId,
        groupId: ?string,
        organizationId: ?string,
        name: string,
        listId: string,
        templateId: string
    )
```

```
class CreateOrderHandler:
    constructor(
        repository: OrderRepositoryInterface
    )

    function invoke(command: CreateOrderCommand): void
        order = Order.create(
            id: command.id,
            groupId: command.groupId ? GroupId.fromString(command.groupId) : null,
            organizationId: command.organizationId ? OrganizationId.fromString(command.organizationId) : null,
            name: command.name,
            listId: ListId.fromString(command.listId),
            templateId: TemplateId.fromString(command.templateId)
        )

        repository.save(order)

        // Command does NOT return anything
```

### 3.4 Query (Read Operation)

```
// @returns OrderDTO
class GetOrderByIdQuery implements QueryInterface:
    constructor(
        id: OrderId
    )
```

```
class GetOrderByIdHandler:
    constructor(
        repository: OrderRepositoryInterface
    )

    function invoke(query: GetOrderByIdQuery): OrderDTO
        order = repository.findById(query.id)

        if order === null:
            throw OrderNotFoundException()

        return OrderDTO.fromEntity(order)
```

### 3.5 Controller

```
class OrderController:
    constructor(
        createAction: CreateOrderAction
    )

    function store(request: CreateOrderRequest): JsonResponse
        resource = createAction(request.getDto())

        return response.json(resource, 201)
```

### 3.6 Resource

```
class OrderResource implements JsonSerializable:
    function jsonSerialize(): array
        return {
            'id': this.id,
            'group_id': this.groupId,
            'organization_id': this.organizationId,
            'name': this.name,
            'description': this.description,
            'status': this.status,
            'list_id': this.listId,
            'template_id': this.templateId,
            'scheduled_at': this.scheduledAt,
            'started_at': this.startedAt,
            'created_at': this.createdAt,
            'updated_at': this.updatedAt,
        }
```

### 3.7 Routes

```
// routes/api
Route.prefix('orders').group(
    Route.post('/', OrderController.store)
    Route.get('/', OrderController.index)
    Route.get('/{id}', OrderController.show)
    Route.put('/{id}', OrderController.update)
    Route.delete('/{id}', OrderController.destroy)
)
```

---

## Checklist per Phase

### PHASE 1: Domain
- [ ] Entity created with public properties
- [ ] Static `create()` method
- [ ] `update()` method
- [ ] Specific methods for state transitions
- [ ] Status is NOT changed directly
- [ ] NO getters/setters
- [ ] Value Objects created
- [ ] Enums created
- [ ] Domain events

### PHASE 2: Infrastructure
- [ ] Repository Interface in Domain
- [ ] Repository Implementation in Infrastructure
- [ ] Mapper/Hydrator
- [ ] ORM Model
- [ ] Migration with constraints

### PHASE 3: Application + HTTP
- [ ] Request class with `getDto()` only
- [ ] Action class
- [ ] Command + Handler (void)
- [ ] Query + Handler (DTO)
- [ ] Controller (uses Action)
- [ ] Resource
- [ ] Routes registered

---

**See also:**
- [Frontend-Backend Integration](frontend-backend-integration.md) - **Full-stack development strategies**
- [critical-rules.md](critical-rules.md) - Critical project rules
- [architecture.md](architecture.md) - DDD Architecture
