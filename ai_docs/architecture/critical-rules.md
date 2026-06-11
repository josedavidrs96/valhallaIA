# Critical Rules - MUST READ

**READ THIS FIRST BEFORE ANY TASK**

These are non-negotiable rules that prevent critical errors in production.

---

## MOST CRITICAL RULES (TOP 5)

### #0: API Routers MUST Catch ALL Domain Exceptions

**CRITICAL:** Every router endpoint MUST catch all domain exceptions and return proper HTTP errors.

**FORBIDDEN:**
```
@router.post('/register')
async def register(request):
    # WRONG: WeakPasswordException not caught → 500 Internal Server Error
    return controller.register(request)
```

**CORRECT:**
```
@router.post('/register')
async def register(request):
    try:
        return controller.register(request)
    except UserAlreadyExistsException as e:
        raise HTTPException(status_code=409, detail=str(e))
    except WeakPasswordException as e:
        raise HTTPException(status_code=422, detail=str(e))
    # Catch ALL possible domain exceptions
```

**Why this is critical:**
- Uncaught exceptions return 500 with no useful message
- Frontend cannot display helpful error to user
- Contract tests will fail
- API contract is broken

**Rules:**
- List ALL domain exceptions that can be thrown by the handler
- Map each exception to appropriate HTTP status code
- Use `str(e)` for user-friendly messages
- Document error responses in API contract

---

### #0.5: Frontend-Backend Features REQUIRE Contract First

**CRITICAL:** When implementing features that involve both frontend and backend, you MUST define the API contract FIRST.

**Read:** [Frontend-Backend Integration](frontend-backend-integration.md)

**Contract Location:** `/docs/api-contracts/{feature}/{endpoint}.md`

**Rules:**
- API contract document created BEFORE any code
- Contract reviewed by both frontend AND backend
- Contract tests written BEFORE backend implementation
- Mocks match contract (for parallel development)
- `make test-contract` passes before merge

---

### #1: Requests NEVER Use Framework Validation

**FORBIDDEN:**
```
function rules(): array { return [...] }  // NEVER
function after(): array { return [...] }  // NEVER
this.validated()  // NEVER
```

**CORRECT:**
```
function getDto(): XxxDto
    // Only map to strongly-typed DTOs
    return new XxxDto(...)
```

**Read before creating/modifying Requests:** [HTTP Requests Pattern](http-requests-pattern.md)

---

### #2: Actions NEVER Contain Business Logic and NEVER Return JSON Directly

**FORBIDDEN in Actions:**
- Direct database access
- `foreach`, `map` for business logic or data transformations
- Validations or calculations
- Business data transformations
- **CRITICAL: Return JSON directly** - Actions must be decoupled from HTTP
- **CRITICAL: Return DTOs** - DTOs are internal objects

**CORRECT in Actions:**
- `verifyAccess()` - Security only
- `commandBus.dispatch()` - Delegate commands
- `resService.getXxxResource()` - Get Resource via ResService
- **Return single Resource (`XxxRes`)** - Allows testing without HTTP context
- **Return array of Resources (`XxxRes[]`)** - For simple lists without pagination
- **Return ResourceCollection** - For lists with pagination metadata
- **Use `map` to transform DTO → Resource** - This is HTTP layer concern, not business logic

**Resource Return Types:**
```
// Single resource
function invoke(...): UserRes
    dto = queryBus.query(new GetUserQuery(id))
    return new UserRes(dto)

// Array of resources (no pagination)
function invoke(...): UserRes[]
    dtos = queryBus.query(new FindUsersQuery(filters))
    return dtos.map(dto => new UserRes(dto))

// ResourceCollection (with pagination)
function invoke(...): UserCollectionRes
    dtos = queryBus.query(new FindUsersQuery(filters))
    resources = dtos.map(dto => new UserRes(dto))
    return new UserCollectionRes(
        items: resources,
        total: count(dtos),
        limit: dto.limit,
        offset: dto.offset
    )
```

**CORRECT in Controller:**
- `response.json(resource)` - Controller converts Res to JSON (only HTTP-specific code)

**Read before creating/modifying Actions:** [HTTP Layer Actions](http-layer-actions.md)

---

### #3: Handlers NEVER Use Direct Database Access

**FORBIDDEN in Handlers:**
```
db.table('users').where(...).get()  // NEVER

db.table('user_tags')
    .where('user_id', userId)
    .get()  // NEVER
```

**CORRECT in Handlers:**
```
// 1. Create Repository Interface in Domain
interface UserTagRepositoryInterface:
    function getUserTagNames(userId: string, userType: string): string[]

// 2. Implement in Infrastructure
class UserTagRepository implements UserTagRepositoryInterface:
    function getUserTagNames(userId: string, userType: string): string[]
        return db.table('user_tags')...  // DB only in Repository

// 3. Inject and use in Handler
class XxxHandler:
    constructor(
        userTagRepository: UserTagRepositoryInterface
    )

    function invoke():
        tags = userTagRepository.getUserTagNames(userId, userType)

// 4. Register in Service Provider
container.bind(UserTagRepositoryInterface, UserTagRepository)
```

**Rule:**
- NEVER access DB directly in Handlers
- ALWAYS create Repository if it doesn't exist
- Direct database access only allowed in Infrastructure layer (Repositories)

---

### #4: IDs Are Value Objects, NOT Strings

**FORBIDDEN:**
```
interface TagRepositoryInterface:
    function getUserIdsByTag(tagId: string): array  // NEVER string

class XxxHandler:
    private function applyFilter(tagId: mixed): void
        users = repository.getUserIdsByTag(tagId)  // NEVER mixed/string
```

**CORRECT:**
```
// 1. Use ValueObject in Repository Interface
interface TagRepositoryInterface:
    function getUserIdsByTag(tagId: TagId): array  // ValueObject

// 2. Convert mixed to ValueObject in Handler
class XxxHandler:
    private function applyFilter(tagId: mixed): void
        tagIdVO = new TagId(getString(tagId))  // Convert first
        users = repository.getUserIdsByTag(tagIdVO)  // Pass ValueObject

// 3. In Repository implementation: use .value()
class TagRepository implements TagRepositoryInterface:
    function getUserIdsByTag(tagId: TagId): array
        return db.table('user_tags')
            .where('tag_id', tagId.value())  // Convert to string here
            .pluck('user_id')
            .all()
```

**Rule:**
- NEVER use `string` for IDs in interfaces/parameters
- ALWAYS use ValueObjects (`TagId`, `UserId`, etc.)
- If doesn't exist: create `class XxxId extends Ulid`
- In Repository implementation: use `.value()` to get string

---

## 0. Identifier Pattern (Dual ID Pattern)

### CRITICAL: Dual Identification System

**This system uses two types of IDs for each entity:**

#### Internal ID (ULID)
- **Purpose:** Unique identifier within the microservice
- **Format:** ULID (26 characters)
- **Examples:** `OrganizationId`, `UserId`, `OrderId`
- **Usage:** Internal relationships, foreign keys, references

```
class UserId extends Ulid
```

#### External ID (AppComposedId)
- **Purpose:** Composite identifier for entities imported from external applications
- **Components:**
  - `app: AppEnum` - Source application
  - `id: string` - ID in external application
- **Examples:** `AppUserId`, `AppOrganizationId`

```
abstract class AppComposedId:
    readonly app: AppEnum
    readonly id: string

class AppUserId extends AppComposedId
```

### When to Use Each One

**Entities IMPORTED from external apps:**
```
class User extends BaseEntity:
    readonly id: UserId                    // Internal ID (ULID)
    readonly appUserId: AppUserId          // External ID
    // ...
```
Need BOTH IDs:
- Internal ID for relationships within system
- AppComposedId for synchronization and avoiding duplicates

**Entities CREATED in this microservice:**
```
class Order extends BaseEntity:
    readonly id: OrderId  // Only internal ID
    // ...
```
Only need internal ID (ULID)
DON'T need AppComposedId because they don't come from outside

---

## 1. Database Performance

**Database can be HUGE** - Always analyze performance impact before any change

### Rules:
- **NEVER execute queries in loops**
- **Minimize number of SQL queries**
- **Fetch all IDs first, then query with IN clause**

### Bad Example:
```
for user in users:
    orders = orderRepository.findByUserId(user.id)
    // WRONG: N queries in loop
```

### Good Example:
```
// 1. Get all user IDs
userIds = users.map(u => u.id)

// 2. Get all orders in ONE query
orders = orderRepository.findByUserIds(userIds)

// 3. Join in application code
for user in users:
    user.orders = orders.filter(o => o.userId === user.id)
```

## 2. Commands in DDD (CQRS)

### CRITICAL: Commands NEVER Return Values

**Commands in CQRS ALWAYS return `void`** - They can ONLY throw exceptions.

### Rules:
- **Commands NEVER return anything** - return type is `void`
- **Generate ID BEFORE the command** and pass it in
- **Commands can ONLY throw exceptions** for errors
- **Use the generated ID** for subsequent operations

### Bad Example:
```
// WRONG: Expecting command to return ID
orderId = commandBus.dispatch(
    new CreateOrderCommand(data)
)
```

### Good Example:
```
// CORRECT: Generate ID first, pass to command
orderId = OrderId.random()

commandBus.dispatch(
    new CreateOrderCommand(
        id: orderId,    // ID passed in
        ...data
    )
)

// Use already-generated orderId for queries or response
order = queryBus.query(new GetOrderByIdQuery(orderId))
```

### Command Structure:
```
class CreateOrderCommand implements CommandInterface:
    constructor(
        id: OrderId,      // ID is PASSED IN
        userId: UserId,
        // ... other data
    )

class CreateOrderHandler:
    function invoke(command: CreateOrderCommand): void  // Returns VOID
        order = Order.create(
            id: command.id,   // Use ID from command
            // ...
        )

        repository.save(order)
        // NO RETURN
```

### Exception: Security/Infrastructure Operations

**CRITICAL:** Commands **NEVER** return values. NO EXCEPTIONS.

**SOLUTION:** For pure infrastructure/security operations like token generation where the generated value MUST be returned immediately:

**Use a Query that modifies system state (documented exception)**

```
// ARCHITECTURAL EXCEPTION: This Query modifies system state (generates token).
// This is acceptable ONLY for security/infrastructure operations where:
// - The generated value MUST be returned immediately
// - The value cannot be retrieved later via a separate Query
// - Alternative would add unnecessary complexity

class GenerateTokenHandler implements QueryHandlerInterface:
    function invoke(query: GenerateTokenQuery): Token
        // Validate credentials
        // Generate token
        return token
```

**When to use this exception:**
- Token/JWT generation (security infrastructure)
- One-time password generation (security)
- External authentication that returns session token
- Sending notification and returning message ID from external service
- NEVER for business logic operations
- NEVER for regular entity creation
- **NEVER use a Command that returns a value** - Use a Query instead

**Key Rule:**
- If you need to return something → Use a **Query** (document the exception)
- If you don't need to return anything → Use a **Command** (returns void)

## 3. Utility Classes

### DO NOT Create New Utilities Without Permission

**Always check existing utilities first in `src/Shared/Framework/Helpers/`:**

Check the codebase for existing helpers before creating new ones:
- ArrayHelper
- AssertHelper
- CacheHelper
- DateHelper
- EmailHelper
- FileHelper
- NumberHelper
- ObjectHelper
- etc.

**Rule:** If you think you need a new utility, first check if an existing helper already solves it.

## 4. Development Environment

### Docker
- All code runs in Docker
- **NEVER execute code directly on host**

### Commands:
```
# CORRECT: Run in Docker
docker-compose exec app composer install
docker-compose exec app artisan test

# WRONG: Run on host (unless explicitly running without Docker)
composer install  # DON'T DO THIS
artisan test      # DON'T DO THIS
```

## 5. No Deprecated Classes

**Rule:** Never use deprecated classes

**How to check:**
- Look for `@deprecated` annotation
- Check with team if unsure
- Use modern alternatives

## 6. No Orphan Code

### Rules:
- Only create code you will **use NOW**
- Don't create entities/classes not immediately needed
- Don't commit unused code

### Bad Example:
```
// Creating entity not used yet
class Payment:
    // ... 100 lines of code
// WRONG: Not using it in current task
```

### Good Example:
```
// Only create what you need for current task
class OrderService:
    function createOrder():
        // Use this NOW
```

## 7. HTTP Layer - Resources

### CRITICAL: Transform DTOs to Resources

**In controllers, ALWAYS transform application DTOs to API Resources**

### Rules:
- **NEVER return DTOs from Queries directly in HTTP responses**
- **ALWAYS create a Resource class** for each endpoint response
- **Transform DTO → Resource** before returning
- **Purpose:** Decouple application layer from HTTP layer

### Why Resources?
- Application DTOs can change without breaking API contracts
- Different endpoints may need different representations of same data
- API versioning becomes easier
- Clear separation of concerns

### Bad Example:
```
// WRONG: Returning Query DTO directly
class UserController:
    function show(id: int):
        userDTO = queryBus.query(
            new GetUserDetailQuery(id)
        )

        // WRONG: Returning DTO directly
        return response.json(userDTO)
```

### Good Example:
```
// CORRECT: Transform DTO to Resource
class UserController:
    function show(id: int):
        // 1. Get DTO from Query
        userDTO = queryBus.query(
            new GetUserDetailQuery(id)
        )

        // 2. Transform to Resource (HTTP-specific)
        return new UserResource(userDTO)
```

### Resource Structure:
```
// app/Http/Resources/UserResource

class UserResource implements JsonSerializable:
    function jsonSerialize(): array
        return {
            'id': this.id.getValue(),
            'name': this.name,
            'email': this.email,
            'phone': this.phone,
        }
```

### Naming Convention:
- Query DTOs: `UserDetailDTO`, `OrderRefDTO`
- Resources: `UserResource`, `OrderResource`
- Suffix is always `Resource` (not `ResponseDTO` or `ViewModel`)

### Location:
- Resources: `app/Http/Resources/`
- Example: `app/Http/Resources/UserResource`

## Quick Checklist Before Starting Any Task

### For ALL Tasks
- [ ] Read Critical Rules (this file)
- [ ] Understand the DDD architecture
- [ ] Check if performance impact on database
- [ ] Verify existing utilities before creating new ones

### For Frontend-Backend Features (MANDATORY)
- [ ] **API contract defined FIRST** in `/docs/api-contracts/`
- [ ] **Contract reviewed by frontend AND backend**
- [ ] **Contract tests written BEFORE backend implementation**
- [ ] **Router catches ALL domain exceptions (no 500 errors!)**
- [ ] **`make test-contract` passes before merge**

See [Frontend-Backend Integration](frontend-backend-integration.md) for development strategies.

### For Backend Implementation
- [ ] Always use Resources for HTTP responses
- [ ] **CRITICAL: If creating/modifying Actions: Read [HTTP Layer Actions](http-layer-actions.md) first**
- [ ] **CRITICAL: If creating/modifying Requests: Read [HTTP Requests Pattern](http-requests-pattern.md) first**
- [ ] **Requests NEVER have `rules()` or `after()` - Only `getDto()`**

---

**See also:**
- [Frontend-Backend Integration](frontend-backend-integration.md) - **Contract-first development, strategies**
- [Architecture](architecture.md) - DDD architecture overview
- [Development Workflow](development-workflow.md) - Implementation phases
- [Application Layer](application-layer.md) - Queries and Commands
- [HTTP Layer Actions](http-layer-actions.md) - **CRITICAL: Actions must be THIN**
- [HTTP Requests Pattern](http-requests-pattern.md) - **CRITICAL: NO framework validation in Requests**
- [Code Quality](code-quality.md) - SOLID principles and best practices
