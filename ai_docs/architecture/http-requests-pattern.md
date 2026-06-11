# HTTP Requests Pattern - NO Framework Validation

**CRITICAL: Requests DO NOT use framework validation**

---

## BEFORE CONTINUING - Read this first:

If you're about to write any of these lines in a Request, **STOP**:

```
function rules(): array       // FORBIDDEN
function after(): array       // FORBIDDEN
this.validated()              // FORBIDDEN
```

**If you see any of these lines in an existing Request, REMOVE THEM.**

---

## Executive Summary

**Requests only map data to strongly typed DTOs. They do NOT have `rules()`, they do NOT have `after()`, they do NOT use `validated()`.**

If something is wrong, **it will fail when constructing the DTO** with a type error.

**Golden rule:** A Request must have ONLY one method: `getDto()`

---

## What NOT to do

### INCORRECT: Using Framework Validation

```
class AddOrderItemsRequest extends AbstractFormRequest:
    // NEVER do this
    function rules(): array
        return {
            'items': ['required', 'array', 'min:1'],
            'items.*.name': ['required', 'string', 'max:255'],
            'items.*.email': ['nullable', 'email', 'max:255'],
            'items.*.phone': ['nullable', 'string', 'max:20']
        }

    // NEVER do this
    function after(): array
        return [
            validator =>
                for index, item in this.input('items', []):
                    if empty(item['email']) && empty(item['phone']):
                        validator.errors().add(...)
        ]

    // NEVER use validated()
    function getDto(): AddOrderItemsDto
        items = this.validated()['items']  // NO!
        // ...
```

**Why is it wrong?**
1. Duplicate validation (Framework + Domain)
2. Not strongly typed
3. Hard to test
4. Mixes HTTP concerns with business rules
5. Not reusable outside HTTP layer

---

## What TO do

### CORRECT: Map to Strongly Typed DTOs

```
class AddOrderItemsRequest extends AbstractFormRequest:
    // ONLY getDto() method
    function getDto(): AddOrderItemsDto
        helper = this.getHelper()

        id = new OrderId(helper.routeString('id'))
        app = AppEnum.from(helper.routeString('app'))

        // Map each item to ItemDto (strongly typed)
        items = helper.getArray('items').map(
            data => new ItemDto(
                name: (string) data['name'],
                email: isset(data['email']) ? (string) data['email'] : null,
                phone: isset(data['phone']) ? (string) data['phone'] : null,
                countryCode: isset(data['country_code']) ? (string) data['country_code'] : null,
                acceptsTerms: isset(data['accepts_terms']) ? (bool) data['accepts_terms'] : true
            )
        )

        return new AddOrderItemsDto(
            app: app,
            id: id,
            items: items  // array<ItemDto>
        )
```

**Benefits:**
1. Strong typing - If it fails, it fails with type error
2. No duplicate validation
3. Easy to test (unit test without HTTP)
4. Reusable (DTOs can be used from CLI, Queue, etc.)
5. Business validation in Handler (where it belongs)

---

## Pattern: Request → DTO → Command → Handler

### 1. ItemDto (Individual Transfer Object)

```
// Apps/Api/Order/AddOrderItems/ItemDto

class ItemDto:
    readonly name: string
    readonly email: ?string
    readonly phone: ?string
    readonly countryCode: ?string
    readonly acceptsTerms: bool
```

### 2. AddOrderItemsDto (Main Transfer Object)

```
// Apps/Api/Order/AddOrderItems/AddOrderItemsDto

class AddOrderItemsDto:
    // @param array<ItemDto> items
    constructor(
        app: AppEnum,
        id: OrderId,
        items: array  // Array of DTOs, NOT array<mixed>
    )
```

### 3. Request (Parse to DTOs)

```
// Apps/Api/Order/AddOrderItems/AddOrderItemsRequest

class AddOrderItemsRequest extends AbstractFormRequest:
    function getDto(): AddOrderItemsDto
        helper = this.getHelper()

        // Parse route params
        id = new OrderId(helper.routeString('id'))
        app = AppEnum.from(helper.routeString('app'))

        // Parse body - map each item to ItemDto
        itemsData = helper.getArray('items')

        items = itemsData.map(
            data => new ItemDto(
                name: (string) data['name'],
                email: isset(data['email']) ? (string) data['email'] : null,
                phone: isset(data['phone']) ? (string) data['phone'] : null,
                countryCode: isset(data['country_code']) ? (string) data['country_code'] : null,
                acceptsTerms: isset(data['accepts_terms']) ? (bool) data['accepts_terms'] : true
            )
        )

        return new AddOrderItemsDto(
            app: app,
            id: id,
            items: items
        )
```

### 4. Command (Application Layer)

```
// src/Order/Application/Commands/.../AddItemsToOrderCommand

class AddItemsToOrderCommand implements CommandInterface:
    // @param array<ItemDto> items
    constructor(
        orderId: OrderId,
        app: AppEnum,
        items: array
    )
```

### 5. Handler (Business Logic)

```
// src/Order/Application/Commands/.../AddItemsToOrderHandler

class AddItemsToOrderHandler implements CommandHandlerInterface:
    function invoke(command: AddItemsToOrderCommand): void
        // Business validation (HERE, not in Request)
        validItems = []
        for itemDto in command.items:
            // Validate business rule: email OR phone required
            if itemDto.email === null && itemDto.phone === null:
                continue  // Skip invalid items

            validItems.append({
                'name': itemDto.name,
                'email': itemDto.email,
                'phone': itemDto.phone,
                'country_code': itemDto.countryCode,
                'accepts_terms': itemDto.acceptsTerms
            })

        if empty(validItems):
            return

        // Delegate to Repository
        repository.addItems(command.orderId, validItems, itemType)

        // Update count, publish events, etc.
```

---

## What type of validation goes where?

| Validation Type | Where | Example |
|-----------------|-------|---------|
| **Strong typing** | Request → DTO | `name: (string) data['name']` |
| **Basic format** | DTO constructor | `new Email(string)` throws exception if invalid |
| **Business rules** | Handler | "email OR phone required" |
| **Complex validation** | Entity or Domain Service | "Only DRAFT orders can be scheduled" |
| **Permissions/Ownership** | Action (via verifyAccess) | Auth verifies group/organization |

---

## Complete Example: Create Order

### Request

```
class StoreOrderRequest extends AbstractFormRequest:
    function getDto(): StoreOrderDto
        helper = this.getHelper()

        return new StoreOrderDto(
            groupId: GroupId.fromStringOrNull(helper.getStringOrNull('group_id')),
            organizationId: OrganizationId.fromStringOrNull(helper.getStringOrNull('organization_id')),
            name: helper.getString('name'),
            description: helper.getStringOrNull('description'),
            listId: new ListId(helper.getString('list_id')),
            templateId: new TemplateId(helper.getString('template_id')),
            scheduledAt: helper.getIntOrNull('scheduled_at')
        )
```

### DTO

```
class StoreOrderDto:
    constructor(
        groupId: ?GroupId,
        organizationId: ?OrganizationId,
        name: string,
        description: ?string,
        listId: ListId,
        templateId: TemplateId,
        scheduledAt: ?int
    )
```

**If something fails:**
- `helper.getString('name')` throws exception if `name` doesn't exist or is not a string
- `new ListId(helper.getString('list_id'))` throws exception if not a valid ULID
- `GroupId.fromStringOrNull()` throws exception if invalid string (not if null)

---

## Arrays of DTOs vs arrays of mixed

### INCORRECT: array<mixed>

```
class AddOrderItemsDto:
    // @param array<{name: string, email?: string}> items
    constructor(
        items: array  // Array of mixed arrays
    )
```

**Problems:**
- Not strongly typed
- Easy to put incorrect data
- Static analysis can't help you
- Not reusable

### CORRECT: array<ItemDto>

```
class AddOrderItemsDto:
    // @param array<ItemDto> items
    constructor(
        items: array  // Array of strongly typed DTOs
    )
```

**Benefits:**
- Strong typing for each item
- Static analysis checks correctly
- IDE autocomplete
- Impossible to put incorrect data

---

## Error Handling

### If data comes wrong from request:

```
// Request body:
{
    "name": null,  // Expected string
    "email": 123    // Expected string|null
}

// In Request:
helper.getString('name')  // Throws exception: "name must be string"

// Or in DTO:
name: (string) data['name']  // Throws TypeError if null
```

### If format is invalid:

```
// Request body:
{
    "id": "not-a-valid-ulid"
}

// In Request:
new OrderId(helper.getString('id'))  // Throws InvalidUlidException
```

### If business rule fails:

```
// In Handler (NOT in Request):
if itemDto.email === null && itemDto.phone === null:
    // Skip or throw domain exception
    throw ItemRequiresEmailOrPhoneException()
```

---

## Testing

### Request Test (Unit)

```
function test_maps_request_data_to_dto(): void
    request = new AddOrderItemsRequest({
        'items': [
            {'name': 'John', 'email': 'john@example.com'}
        ]
    })
    request.setRouteResolver(() => new Route('POST', '/orders/{app}/{id}/items', []))
    request.route().setParameter('id', '01HQXXX...')
    request.route().setParameter('app', 'web')

    dto = request.getDto()

    assert(dto instanceof AddOrderItemsDto)
    assertEqual(1, count(dto.items))
    assert(dto.items[0] instanceof ItemDto)
    assertEqual('John', dto.items[0].name)
```

### Handler Test (Unit)

```
function test_filters_invalid_items(): void
    command = new AddItemsToOrderCommand(
        orderId: new OrderId('01HQXXX...'),
        app: AppEnum.web,
        items: [
            new ItemDto('John', 'john@example.com', null, null, true),
            new ItemDto('Jane', null, null, null, true)  // Invalid: no email/phone
        ]
    )

    handler.invoke(command)

    // Verify only valid item was added
    repositoryMock.shouldHaveReceived('addItems')
        .once()
        .with(
            any(),
            satisfies(items => count(items) === 1),
            any()
        )
```

---

## Checklist Before Committing Request

- [ ] **Does NOT have `rules()` method**
- [ ] **Does NOT have `after()` method**
- [ ] **Does NOT use `validated()`**
- [ ] **Has ONLY `getDto()` method**
- [ ] **Returns strongly typed DTO**
- [ ] **Arrays are arrays of DTOs** (not `array<mixed>`)
- [ ] **Uses `helper.getString()`, `getInt()`, etc.** for parsing
- [ ] **Explicit casts** when necessary: `(string)`, `(int)`, `(bool)`

---

## See Also

- [architecture.md](architecture.md) - DDD and Hexagonal Architecture
- [http-layer-actions.md](http-layer-actions.md) - Actions THIN pattern
- [critical-rules.md](critical-rules.md) - Critical project rules
- [http-layer-patterns.md](http-layer-patterns.md) - HTTP Layer patterns
