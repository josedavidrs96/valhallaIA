# HTTP Layer - Architecture Patterns

## Table of Contents
- [Folder Structure](#folder-structure)
- [Action-Request-Dto-Res Pattern](#action-request-dto-res-pattern)
- [Request (No rules)](#1-request-no-rules)
- [Dto (Transfer Object)](#2-dto-transfer-object)
- [Action (Returns Resource)](#3-action-returns-resource)
- [ResService (Converts DTO → Res)](#4-resservice-converts-dto--res)
- [Res (JsonSerializable)](#5-res-jsonserializable)
- [Controller (Orchestrates Actions)](#6-controller-orchestrates-actions)
- [Complete Examples](#complete-examples)

---

## Folder Structure

```
Apps/Api/
├── Order/                        # By domain/aggregate
│   ├── Store/                   # By action
│   │   ├── StoreOrderAction
│   │   ├── StoreOrderRequest
│   │   └── StoreOrderDto
│   ├── Update/
│   │   ├── UpdateOrderAction
│   │   ├── UpdateOrderRequest
│   │   └── UpdateOrderDto
│   ├── Show/
│   │   ├── ShowOrderAction
│   │   └── ShowOrderRequest
│   ├── Index/
│   │   ├── IndexOrderAction
│   │   └── IndexOrderRequest
│   ├── Shared/
│   │   ├── Services/
│   │   │   └── OrderResService       # DTO → Res converter
│   │   └── OrderRes                  # API Resource
│   └── OrderController               # HTTP orchestration
```

---

## Action-Request-Dto-Res Pattern

This is the complete flow of an HTTP request:

```
HTTP Request
    ↓
Request.getDto() → Dto
    ↓
Action.invoke(Dto) → Resource
    ↓
    ├─→ CommandBus (writes)
    ├─→ QueryBus (reads) → Domain DTO
    └─→ ResService.fromDto(Domain DTO) → API Res
            ↓
Controller → JsonResponse(Res)
```

---

## 1. Request (No rules)

### INCORRECT - DO NOT define rules()

```
// DO NOT DO THIS
class StoreOrderRequest extends AbstractFormRequest:
    // DO NOT include rules()
    function rules(): array
        return {
            'name': 'required|string|max:255',
            'description': 'nullable|string'
        }
```

### CORRECT - Only getDto()

```
// Apps/Api/Order/Store/StoreOrderRequest

class StoreOrderRequest extends AbstractFormRequest:
    // Only parse and convert to DTO
    function getDto(): StoreOrderDto
        return new StoreOrderDto(
            groupId: GroupId.fromStringOrNull(
                helper.getStringOrNull('group_id')
            ),
            organizationId: OrganizationId.fromStringOrNull(
                helper.getStringOrNull('organization_id')
            ),
            name: helper.getString('name'),
            description: helper.getStringOrNull('description'),
            listId: new ListId(helper.getString('list_id')),
            templateId: new TemplateId(helper.getString('template_id'))
        )
```

**Reasons:**
- Validation is done in another layer (domain or request validation middleware)
- The Request should only parse and convert types
- Keeps the Request thin and focused

---

## 2. Dto (Transfer Object)

```
// Apps/Api/Order/Store/StoreOrderDto

class StoreOrderDto:
    readonly groupId: ?GroupId
    readonly organizationId: ?OrganizationId
    readonly name: string
    readonly description: ?string
    readonly listId: ListId
    readonly templateId: TemplateId
```

**Characteristics:**
- `readonly` - Immutable
- Only public properties
- No logic, only data
- Uses Value Objects (GroupId, ListId, etc.)

---

## 3. Action (Returns Resource, NOT JsonResponse) - CRITICAL RULE

### CRITICAL: Actions MUST return Resources (XxxRes), NEVER JsonResponse

**This is one of the most important rules in the project. Actions must be HTTP-agnostic.**

### INCORRECT - DO NOT return JsonResponse, arrays or DTOs

```
// DO NOT DO THIS - Actions must be decoupled from HTTP
function invoke(StoreOrderDto dto): JsonResponse
    // ...
    return new JsonResponse([...])  // WRONG

// DO NOT DO THIS
function invoke(StoreOrderDto dto): array
    // ...
    return ['id': id, 'name': name]  // WRONG

// DO NOT DO THIS
function invoke(StoreOrderDto dto): OrderDto
    // ...
    return dto  // WRONG
```

### CORRECT - Always return Resource (XxxRes)

```
// Apps/Api/Order/Store/StoreOrderAction

class StoreOrderAction:
    constructor(
        commandBus: CommandBusInterface,
        resService: OrderResService
    )

    // Returns Resource, NOT JsonResponse
    function invoke(StoreOrderDto dto): OrderRes
        // 1. Generate ID at HTTP application layer
        orderId = OrderId.random()

        // 2. Dispatch command (write operation)
        command = new CreateOrderCommand(
            id: orderId,
            groupId: dto.groupId,
            organizationId: dto.organizationId,
            name: dto.name,
            description: dto.description,
            listId: dto.listId,
            templateId: dto.templateId
        )

        commandBus.dispatch(command)

        // 3. Use ResService to convert Domain DTO → API Res
        // Return Resource directly, NOT JsonResponse
        return resService.getOrderResource(orderId)
```

**Flow:**
1. Generate ID (in HTTP layer)
2. Dispatch Command (write)
3. Query DTO (read) via ResService
4. Convert DTO → Res via ResService
5. Return Resource (Controller handles JsonResponse)

**Critical Rule (MUST FOLLOW):**
- **Actions NEVER return `JsonResponse`** - Actions must be decoupled from HTTP layer for testability
- **Actions NEVER return arrays** - Use Resources for consistent API responses
- **Actions NEVER return DTOs** - DTOs are internal transfer objects, not API responses
- **Actions ALWAYS return Resources (`XxxRes`)** - This allows testing Actions without HTTP context
- **Controller converts Resource to JsonResponse** - `response.json(resource)` - This is the ONLY HTTP-specific code
- **ResService converts DTOs/Entities to Resources** - Located in `Apps/Api/{Module}/Shared/Services/XxxResService`
- **Resources implement `JsonSerializable`** - Located in `Apps/Api/{Module}/Shared/XxxRes`

---

## 4. ResService (Converts DTO → Res)

```
// Apps/Api/Order/Shared/Services/OrderResService

class OrderResService:
    constructor(
        queryBus: QueryBusInterface
    )

    // For a single entity
    function getOrderResource(orderId: OrderId): OrderRes
        query = new GetOrderByIdQuery(orderId)
        orderDto = queryBus.query(query)

        return fromDto(orderDto)

    // For collections
    // @param OrderDto[] dtos
    // @return OrderRes[]
    function getOrderResourceCollection(dtos: array): OrderRes[]
        return dtos.map(dto => fromDto(dto))

    // Private mapping
    private function fromDto(dto: OrderDto): OrderRes
        return new OrderRes(
            id: dto.id,
            groupId: dto.groupId,
            organizationId: dto.organizationId,
            name: dto.name,
            description: dto.description,
            listId: dto.listId,
            templateId: dto.templateId,
            status: dto.status,
            scheduledAt: dto.scheduledAt,
            startedAt: dto.startedAt,
            completedAt: dto.completedAt,
            totalItems: dto.totalItems,
            processedItems: dto.processedItems,
            failedItems: dto.failedItems,
            createdAt: dto.createdAt,
            updatedAt: dto.updatedAt
        )
```

**Responsibilities:**
- Query domain DTOs via QueryBus
- Convert domain DTOs → API Res
- Handle collections
- Centralize conversion logic

---

## 5. Res (JsonSerializable)

```
// Apps/Api/Order/Shared/OrderRes

class OrderRes implements JsonSerializable:
    constructor(
        id: OrderId,
        groupId: ?GroupId,
        organizationId: ?OrganizationId,
        name: string,
        description: ?string,
        listId: ListId,
        templateId: TemplateId,
        status: OrderStatusEnum,
        scheduledAt: ?int,
        startedAt: ?int,
        completedAt: ?int,
        totalItems: int,
        processedItems: int,
        failedItems: int,
        createdAt: int,
        updatedAt: int
    )

    // @return array<string, mixed>
    function jsonSerialize(): array
        return {
            'id': this.id.getValue(),
            'groupId': this.groupId?.getValue(),
            'organizationId': this.organizationId?.getValue(),
            'name': this.name,
            'description': this.description,
            'listId': this.listId.getValue(),
            'templateId': this.templateId.getValue(),
            'status': this.status.value,
            'scheduledAt': this.scheduledAt,
            'startedAt': this.startedAt,
            'completedAt': this.completedAt,
            'totalItems': this.totalItems,
            'processedItems': this.processedItems,
            'failedItems': this.failedItems,
            'createdAt': this.createdAt,
            'updatedAt': this.updatedAt
        }
```

**Characteristics:**
- `readonly` - Immutable
- `implements JsonSerializable` - Automatic serialization
- Converts Value Objects to primitives (getValue())
- Converts Enums to strings (.value)

---

## 6. Controller (Orchestrates Actions and handles JsonResponse)

```
// Apps/Api/Order/OrderController

class OrderController:
    function store(
        request: StoreOrderRequest,
        action: StoreOrderAction
    ): JsonResponse
        // Action returns OrderRes (Resource), NOT JsonResponse
        order = action.invoke(request.getDto())

        // Controller converts Resource to JsonResponse
        return response.json(order, 201)

    function show(
        id: string,
        action: ShowOrderAction
    ): JsonResponse
        order = action.invoke(id)
        // DO NOT wrap in {'data': ...} - Resource already implements JsonSerializable
        return response.json(order)

    function index(
        request: IndexOrderRequest,
        action: IndexOrderAction
    ): JsonResponse
        // Action returns array<OrderRes>
        orders = action.invoke(request.getDto())

        return response.json(orders)
```

**Responsibilities:**
- Inject Request and Action
- Call Request.getDto() or Request.getXxx()
- Call Action.invoke() - receives Resource (XxxRes)
- Convert Resource to JsonResponse with `response.json(resource)`
- Set appropriate HTTP status codes (201 for create, 200 for others)

**Critical Rules:**
- **NO wrap in `['data': ...]`** - Resource already implements `JsonSerializable`
- **Controller handles JsonResponse conversion** - This is the only HTTP-specific code
- **Actions are HTTP-agnostic** - Can be tested without HTTP context

---

## Complete Examples

### Example 1: Create (POST)

**Request:**
```http
POST /api/orders
{
    "name": "Summer Order",
    "group_id": "01H2K3M4N5P6Q7R8S9T0VW",
    "list_id": "01H2K3M4N5P6Q7R8S9T0VX",
    "template_id": "01H2K3M4N5P6Q7R8S9T0VY"
}
```

**Flow:**
1. `StoreOrderRequest.getDto()` → `StoreOrderDto`
2. `StoreOrderAction.invoke(dto)` →
   - Generate ID
   - Dispatch `CreateOrderCommand`
   - Query via `ResService`
   - Return `OrderRes`
3. Controller: `response.json(OrderRes, 201)`

**Response:**
```json
{
    "id": "01H2K3M4N5P6Q7R8S9T0VZ",
    "name": "Summer Order",
    "status": "draft",
    ...
}
```

### Example 2: Index (GET)

**Request:**
```http
GET /api/orders?group_id=01H2K3M4N5P6Q7R8S9T0VW
```

**Flow:**
1. `IndexOrderRequest.getGroupId()` → `GroupId`
2. `IndexOrderAction.invoke(groupId)` →
   - Query `FindOrdersByGroupIdQuery`
   - Get `OrderDto[]`
   - Convert via `ResService.getOrderResourceCollection()`
   - Return `OrderRes[]`
3. Controller: `response.json(OrderRes[])`

**Response:**
```json
[
    {
        "id": "01H2K3M4N5P6Q7R8S9T0VZ",
        "name": "Summer Order",
        ...
    },
    {
        "id": "01H2K3M4N5P6Q7R8S9T0WA",
        "name": "Winter Order",
        ...
    }
]
```

---

## Implementation Checklist

When implementing a new endpoint, follow this checklist:

- [ ] **Request** without `rules()`, only `getDto()` or `getXxx()`
- [ ] **Dto** readonly with public properties
- [ ] **CRITICAL: Action does NOT return `JsonResponse`** - Actions must be decoupled from HTTP for testability
- [ ] **CRITICAL: Action does NOT return arrays** - Use Resources for consistent responses
- [ ] **CRITICAL: Action does NOT return DTOs** - DTOs are internal objects, not API responses
- [ ] **Action ALWAYS returns Resource (`XxxRes`)** - Allows testing Actions without HTTP context
- [ ] **Action** uses `ResService` to convert DTOs/Entities → Res (NO direct queries in Action)
- [ ] **ResService** located in `Apps/Api/{Module}/Shared/Services/XxxResService`
- [ ] **ResService** has `getXxxResource()` and `getXxxResourceCollection()` methods
- [ ] **ResService** uses `QueryBus` internally to fetch DTOs/Entities (NO direct queries)
- [ ] **Res** located in `Apps/Api/{Module}/Shared/XxxRes`
- [ ] **Res** implements `JsonSerializable` interface
- [ ] **Res** converts Value Objects to primitives in `jsonSerialize()` method
- [ ] **Controller** delegates to Action, receives Resource (not JsonResponse)
- [ ] **Controller** converts Resource to JsonResponse with `response.json(resource)`
- [ ] **Controller** does NOT wrap in `['data': ...]` - Resource already implements JsonSerializable

---

## Anti-Patterns (DO NOT DO)

### Request with rules()
```
function rules(): array { ... }  // NO!
```

### Action returning JsonResponse
```
function invoke(): JsonResponse { ... }  // NO! Actions must be decoupled from HTTP
```

### Action returning array
```
function invoke(): array { ... }  // NO!
```

### Action returning DTO
```
function invoke(): OrderDto { ... }  // NO!
```

### Controller manually mapping
```
function store(request):
    // ...
    return response.json({
        'id': order.id,  // DO NOT map here!
        'name': order.name
    })
```

---

## Benefits of this Pattern

1. **Clear separation of responsibilities**
   - Request: Parse
   - Action: Orchestration
   - ResService: Conversion
   - Res: Serialization

2. **Testable**
   - Each piece can be tested independently

3. **Reusable**
   - ResService can be used from multiple Actions

4. **Type-safe**
   - Typed JsonResponse
   - Readonly Res with types

5. **Consistent**
   - All endpoints follow the same pattern
