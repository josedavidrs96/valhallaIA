# Application Layer (CQRS)

Complete guide to Queries, Commands, Events, and Process Managers in the Application layer.

## Queries (Read Operations)

### Important Rules

1. **All queries must have proper documentation**
   ```
   // @see GetUserByIdHandler
   // @returns UserDTO
   class GetUserByIdQuery implements QueryInterface
   ```

2. **Handlers are stateless**
   - All properties are `readonly`
   - For caching, use cache system, not properties

   ```
   class GetUserByIdHandler:
       constructor(
           readonly repository: UserRepository
       )
   ```

### Query Return Types (Allowed)

Allowed returns:
- ReadModel directly
- DTO with simple ValueObjects and Enums
- Primitives (int, string, bool, float)
- Collections/arrays

Not allowed:
- Entities (use DTOs instead)
- Complex mutable objects

### DTOs Can Have Methods

#### Allowed - Decorating Data:
```
class OrderDTO:
    function getAgeFormatted(): string
        return "3 days and 2 hours"

    function getDateFormatted(): string
        return this.date.format('Y-m-d H:i')
```

#### Not Allowed - Business Rules:
```
class UserDTO:
    function isSpecialUser(): bool
        // WRONG: Business rule belongs in Entity or ReadModel
        return this.type === 'VIP'
```

**Rule:** DTOs can format/decorate data, but cannot contain business logic.

### Query Naming Conventions

#### Verbs:

**Get** - Always expects to find, throws error if not found
```
GetUserByIdQuery          // Throws UserNotFoundException if not found
GetOrderByIdQuery         // Throws OrderNotFoundException
```

**Find** - May or may not find element
```
FindUsersByStatusQuery    // Returns empty array if none found
FindOrderByDateQuery      // Returns null if not found
```

**Search** - For filtered searches, ElasticSearch, etc.
```
SearchUsersQuery          // With filters, pagination
SearchOrdersQuery         // Complex search criteria
```

#### Adjectives:

**Ref** - Returns DTO with minimum data
```
GetUserRefQuery
// Returns: id, name, email (minimal fields)
```

**Detail** - Returns DTO with all entity info (entity only, no relations)
```
GetUserDetailQuery
// Returns: All user fields, but NO orders, NO organization
```

**Full** - Returns DTO with all entity info and all possible relations
```
GetUserFullQuery
// Returns: All user fields + orders + organization + preferences
```

**List** - Returns paginated, denormalized data
```
SearchUserListQuery
// Returns: Paginated results with denormalized data
// Example: Includes status description, not just status ID
```

### Adding Fields to Existing Queries

#### Scenario 1: Useful for any consumer + no extra cost
→ **Add to existing query**

```
class GetUserDetailQuery:
    // Add field that's useful for everyone
    preferredLanguage: string  // No extra query needed
```

#### Scenario 2: Only needed for specific use + high cost
→ **Choose one option:**

**Option A:** Add parameter
```
class GetUserDetailQuery:
    constructor(
        id: UserId,
        withMarketingInfo: bool = false  // Optional, expensive
    )

class UserDetailDTO:
    marketingInfo: ?MarketingInfoDTO = null
```

**Option B:** Create new query (if very useful)
```
GetUserMarketingRefQuery  // Specific business purpose
```

### Performance Rules

#### Forbidden: Queries in Loops

**Bad Example:**
```
for user in users:
    orders = queryBus.query(
        new GetOrdersByUserIdQuery(user.id)
    )
    // WRONG: N queries
```

**Good Example:**
```
// 1. Get all user orders in ONE query
userIds = users.map(u => u.id)
allOrders = queryBus.query(
    new GetOrdersByUserIdsQuery(userIds)  // Single query with IN clause
)

// 2. Join in application code
for user in users:
    user.orders = allOrders.filter(o => o.userId === user.id)
```

#### Required: Minimize SQL Queries

**Example:** Get user with all orders and reviews
```
// Maximum 2 queries:

// 1. Get all user orders
orders = getOrders(userId)

// 2. Get all reviews in ONE query
orderIds = orders.map(o => o.id)
reviews = getReviewsByOrderIds(orderIds)

// 3. Join in application code
for order in orders:
    order.review = reviews[order.id] ?? null
```

## Commands (Write Operations)

### Command Structure

```
// @see CreateUserHandler
class CreateUserCommand implements CommandInterface:
    constructor(
        id: UserId,        // ID is PASSED IN
        name: string,
        email: string,
        organizationId: OrganizationId
    )
```

### Handler Structure

```
class CreateUserHandler:
    constructor(
        repository: UserRepository,
        eventBus: EventBus
    )

    function invoke(command: CreateUserCommand): void  // Returns VOID
        // 1. Create entity (with ID from command)
        user = User.create(
            id: command.id,
            name: command.name,
            email: command.email,
            organizationId: command.organizationId
        )

        // 2. Persist
        repository.save(user)

        // 3. Publish events
        eventBus.publishEvents(user.pullDomainEvents())

        // NO RETURN - Commands return void, only throw exceptions
```

### Command Rules

**CRITICAL:**
- Commands **NEVER return anything** - return type is `void`
- Commands can **ONLY throw exceptions** for errors
- **ID is generated BEFORE the command** and passed in
- Commands should be **imperative** (CreateUser, UpdateOrder)
- Handlers should be **stateless**
- Commands should contain **only data** (no logic)
- Business logic belongs in **Entity** or **Domain Service**

### Usage Pattern

```
// CORRECT: Generate ID first, pass to command, command returns void
userId = UserId.random()
commandBus.dispatch(
    new CreateUserCommand(
        id: userId,
        name: 'John Doe',
        email: 'john@example.com',
        organizationId: organizationId
    )
)
// userId already available, no return needed

// WRONG: Expecting return value from command
userId = commandBus.dispatch(
    new CreateUserCommand(...)  // Commands return void!
)
```

## Process Managers (Saga/Cron)

### When to Use

- Long processes requiring many commands
- Cron jobs
- Complex workflows

### Location
`Application/ProcessManagers`

### Naming Convention

- Command suffix: `Process`
- Handler suffix: `ProcessHandler`

### Example

```
// @see SendDailyReportProcessHandler
class SendDailyReportProcess implements CommandInterface:
    constructor(
        organizationId: OrganizationId,
        date: DateTime
    )

class SendDailyReportProcessHandler:
    constructor(
        commandBus: CommandBus,
        queryBus: QueryBus
    )

    function invoke(process: SendDailyReportProcess): void
        // 1. Get data
        orders = queryBus.query(
            new GetOrdersByDateQuery(process.date)
        )

        // 2. Generate report
        report = generateReport(orders)

        // 3. Send email
        commandBus.dispatch(
            new SendEmailCommand(report)
        )

        // No output - Process Managers have no return value
```

### Important Notes

**Process Managers have NO output**
- They orchestrate other commands/queries
- No return value
- Side effects only (emails, events, etc.)

**Advantages:**
- Easier to test
- More reusable
- Less coupling
- Correct architectural location

**Disadvantages:**
- More code to write (trade-off for better architecture)

## Event Listeners

### Event Content Best Practices

#### Wrong Approach 1: Only store ID
```
class UserUpdatedEvent:
    constructor(userId: int)
// WRONG: Consumer has to figure everything out
```

#### Wrong Approach 2: Store everything
```
class UserUpdatedEvent:
    constructor(
        user: User,
        oldData: array,
        newData: array
    )
// WRONG: Too much data, coupling
```

#### Correct Approach 3: Store minimal relevant data
```
class UserUpdatedEvent:
    constructor(
        userId: UserId,
        oldEmail: ?string = null,
        newEmail: ?string = null
    )
// CORRECT: Only event-relevant data
```

### Consistency Problem

```
// UserUpdatedEvent contains new name
class OnUserUpdateNameUpdateOrganization:
    function invoke(event: UserUpdatedEvent): void
        // PROBLEM: Is this the current name?
        // Another process might have changed user data

        // SOLUTION: Always read from DB
        user = userRepository.findById(event.userId)
        // Now we have the latest data
```

**Rule:** With eventual consistency, always read latest data from DB, don't trust event data.

### Best Practices

- Analyze what information is relevant for each event
- Include change log information
- Keep event payload small
- For specific field logic:
  - Option 1: Create separate event for basic data changes
  - Option 2: Add subtype indicator in event

### Event Types

#### Business Events
Location: `Application/Listeners`
- React to domain events
- Business logic listeners

#### Infrastructure Events
Location: `Infrastructure/Listeners`
- React to external systems (message queues, webhooks)
- Logging, change tracking
- Not business logic

---

**See also:**
- [Architecture](architecture.md) - BC communication
- [Infrastructure](infrastructure.md) - Repositories
- [Critical Rules](critical-rules.md) - Performance and CQRS rules
- [Code Quality](code-quality.md) - SOLID principles
