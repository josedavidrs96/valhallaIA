# Infrastructure Layer

Complete guide to Repositories, Tables, Entities, and Hydrators.

## Repositories

### Basic Rules

1. **Repositories are stateless**
   - No state properties
   - Only readonly properties in exceptional cases
   - All dependencies injected via constructor

2. **Public methods return ONLY:**
   - Entities
   - ReadModels
   - Collections
   - Arrays (of entities or readmodels)
   - Scalar values (int, string, bool, float)

3. **Never return raw database query data directly**
   - Don't return: Raw query builder results
   - Return: Hydrated entities or ReadModels

## ReadModels

**CRITICAL:** Repository methods that return query data MUST use ReadModels instead of generic arrays.

### What is a ReadModel?

A ReadModel is a **read-only DTO** optimized for queries:
- Located in `Domain/ReadModels/`
- Name ends with `RM` or `ReadModel`
- Always `readonly` with public properties
- NO business logic
- Used for complex queries and data projections

### When to Use ReadModels

Use ReadModels when:
1. Repository method returns query data that's not a full entity
2. Aggregating data from multiple tables
3. Projecting a subset of entity properties
4. Returning computed/derived data
5. Any method that would return generic arrays

### INCORRECT - Returning mixed arrays

```
// @return array<{user_id: string|null, name: string, email: string|null}>
function getContactList(listId: ListId): array
    results = db.query(/* ... */)

    return results.map(r => {
        'user_id': r.user_id,
        'name': r.name,
        'email': r.email,
    })
```

### CORRECT - Using ReadModel

**Step 1:** Create ReadModel in `Domain/ReadModels/`

```
// src/Marketing/List/Domain/ReadModels/ListContactRM

class ListContactRM:
    readonly userId: ?string
    readonly name: string
    readonly email: ?string
    readonly phone: ?string
    readonly countryCode: ?string
    readonly acceptsMarketing: bool
```

**Step 2:** Update Repository Interface

```
// src/Marketing/List/Domain/Repositories/MarketingListRepositoryInterface

interface MarketingListRepositoryInterface:
    // @return ListContactRM[]
    function getListContacts(listId: ListId, app: AppEnum): ListContactRM[]
```

**Step 3:** Implement in Repository

```
// src/Marketing/List/Infrastructure/Persistence/MarketingListRepository

function getListContacts(listId: ListId, app: AppEnum): ListContactRM[]
    results = ListClientModel.query()
        .where('list_id', listId.value())
        .get()

    return results.map(result =>
        new ListContactRM(
            userId: result.user_id,
            name: result.name,
            email: result.email,
            phone: result.phone,
            countryCode: result.country_code,
            acceptsMarketing: result.accepts_marketing
        )
    )
```

**Step 4:** Use in QueryHandler

```
// src/Marketing/List/Application/Queries/GetListContactsHandler

function invoke(query: GetListContactsQuery): ListContactRM[]
    // Returns ListContactRM[] with full type safety
    contacts = repository.getListContacts(query.listId, query.app)

    // Access properties with autocomplete and type checking
    for contact in contacts:
        email = contact.email      // Type-safe property access
        name = contact.name        // IDE autocomplete works
        accepts = contact.acceptsMarketing

    return contacts
```

### ReadModel Naming Convention

- **Suffix with `RM`** for ReadModel: `ListContactRM`, `OrderStatsRM`
- **Or use full `ReadModel`**: `ListContactReadModel`, `OrderStatsReadModel`
- Name describes the **data contents**, not the usage

### ReadModel Benefits

1. **Type Safety** - Static analysis validates all property access
2. **IDE Support** - Full autocomplete for properties
3. **Refactoring** - Easy to find all usages
4. **Documentation** - Clear contract of returned data
5. **Immutability** - `readonly` prevents accidental mutations

### Repository Structure

```
class UserRepository implements UserRepositoryInterface:
    constructor(
        db: Database,
        hydrator: UserHydrator
    )

    function findById(id: UserId): ?User
        row = db.table('users')
            .where('id', id.getValue())
            .first()

        if row === null:
            return null

        return hydrator.hydrate(row)

    function save(user: User): void
        data = hydrator.dehydrate(user)

        if user.id().getValue() === 0:
            db.table('users').insert(data)
        else:
            db.table('users')
                .where('id', user.id().getValue())
                .update(data)
```

### Complex Repositories

#### Problem: Repository Growing Too Large

**Solution:** Extract methods to separate file

```
// Main repository
class UserRepository implements UserRepositoryInterface:
    function findById(id: UserId): ?User
    function save(user: User): void

// Separate file for complex queries
class UserRMQueryRepository:
    constructor(
        db: Database
    )

    function findUserWithStatsRM(id: UserId): ?UserStatsRM
        // Complex query logic here
```

**Note:** No interface needed for now (too verbose for current needs)

### Method Naming

#### Bad - Describes usage:
```
findUserRMAutocomplete()  // What is it used for
```

#### Good - Describes content:
```
findUserWithStatsRM()     // What it contains
```

**Rule:** Name should describe WHAT it returns, not WHERE it's used.

---

## Tables

### Table Objects

For each table, create a Table class with:
- `TABLE_NAME` constant
- Documentation with all properties and types

#### Example:
```
// @property int id
// @property string name
// @property string surname
// @property string email
// @property ?string phone
// @property ?string mobile
// @property int organization_id
// @property string created_at
// @property string updated_at
// @property int type
// @property bool is_special

class UserTable:
    const TABLE_NAME = 'users'
```

### Rules:

1. **TABLE_NAME constant**
   - This is the ONLY constant used in code to reference table
   - Never use string literals like `'users'` in queries

2. **Define all properties with types in documentation**
   - Use `?` for nullable fields
   - Use proper types: int, string, bool, float

3. **Benefits:**
   - IDE autocomplete
   - Static analysis type checking
   - Documentation

### Using Table Objects

```
// CORRECT
db.table(UserTable.TABLE_NAME)
db.table(UserTable.TABLE_NAME).where('id', id)

// WRONG
db.table('users')  // String literal
```

---

## Entities

### Entities on Legacy Tables

#### Rule 1: Entity is Not Guilty of Obsolete DB Design

Don't transfer infrastructure problems to entities.

**Problem Example:**

Table `payments` has fields:
- `order_id` (always -1 for eCommerce)
- `legacy_type` (always 2 for eCommerce)

#### Wrong - Legacy fields leak into entity:
```
class Payment:
    orderId: int      // Legacy cruft
    legacyType: int   // Legacy cruft
    amount: float     // Business field
```

#### Correct - Entity only has business fields:
```
class Payment:
    id: PaymentId
    amount: Money
    status: PaymentStatus
    // Only business fields, no legacy cruft

// Handle legacy in Hydrator/Repository
class PaymentHydrator:
    function hydrate(row): Payment
        // Map only business fields
        return new Payment(
            id: new PaymentId(row.id),
            amount: new Money(row.amount),
            status: PaymentStatus.from(row.status)
        )

    function dehydrate(payment: Payment): array
        return {
            'id': payment.id.getValue(),
            'amount': payment.amount.getValue(),
            'status': payment.status.value,
            // Set legacy fields here
            'order_id': -1,
            'legacy_type': 2
        }
```

### Rule 2: No Orphan Code

- Only create code you will use **NOW**
- Don't create Entity if not using it yet
- Don't commit unused code

#### Bad:
```
// Creating complete entity not used in current task
class Payment:
    // ... 200 lines of code
    // But not using it yet!
```

#### Good:
```
// Only create what you need for current task
class OrderService:
    function createOrder():
        // Using this NOW in current task
```

### Anemic Entities

#### Business Logic in Entities:
Should be in entity:
- Validate data and maintain invariants
- Business relationships within the domain
- Calculations and business rules specific to this entity

```
class User:
    function changeEmail(newEmail: string): void
        // Validation (invariant)
        if !isValidEmail(newEmail):
            throw InvalidEmailException(newEmail)

        this.email = newEmail
        this.recordEvent(new UserEmailChangedEvent(this.id, newEmail))

    function isSpecial(): bool
        // Business rule specific to user
        return this.type === UserType.VIP
```

#### Business Logic in Domain Services:
Should be in domain service:
- Rules involving external entities
- Rules between different instances of entities
- Complex validations requiring repository access

```
class ValidateOrderService:
    constructor(
        repository: OrderRepository
    )

    function canCreateOrder(
        organizationId: OrganizationId,
        datetime: DateTime,
        partySize: int
    ): bool
        // Needs repository - can't be in entity
        existingOrder = repository.findByOrganizationAndDatetime(
            organizationId,
            datetime
        )

        return existingOrder === null
```

---

## Hydrators

### Purpose

Transform between:
- Database rows (raw objects/arrays) → Entities/ReadModels
- Entities → Database arrays

### Hydrator Structure

```
class UserHydrator:
    function hydrate(row): User
        // Handle both object and array
        return new User(
            id: new UserId(row.id),
            name: row.name,
            surname: row.surname,
            email: row.email,
            phone: row.phone ?? null,
            organizationId: new OrganizationId(row.organization_id),
            type: UserType.from(row.type),
            isSpecial: row.is_special
        )

    function dehydrate(user: User): array
        return {
            'id': user.id.getValue(),
            'name': user.name,
            'surname': user.surname,
            'email': user.email,
            'phone': user.phone,
            'organization_id': user.organizationId.getValue(),
            'type': user.type.value,
            'is_special': user.isSpecial ? 1 : 0
        }
```

### Hydrating Collections

```
class UserHydrator:
    function hydrateMany(rows: array): User[]
        return rows.map(row => this.hydrate(row))
```

---

## Aggregate/Entity Design

Before writing entity code, use this canvas:
https://github.com/ddd-crew/aggregate-design-canvas

### Brainstorming Topics:

1. **Properties**
   - What data does the entity hold?

2. **Validation Rules** (invariants)
   - What rules must ALWAYS be true?
   - Example: Email must be valid format

3. **Exceptions to Rules**
   - How to handle edge cases?
   - Example: Rounding cents causes total mismatch

4. **Events**
   - Which domain events trigger?
   - Example: UserCreated, UserEmailChanged

5. **States and Transitions**
   - What states can entity be in?
   - How does it transition between states?
   - Example: Order: Pending → Confirmed → Cancelled

6. **Performance:**
   - Aggregate or multiple entities?
   - Data volume expectations
   - Query limits and pagination

7. **Commands and Main Queries**
   - What operations will be performed?
   - What queries will be common?

8. **Concurrency**
   - Will there be concurrency conflicts?
   - Example: Two processes updating same order

9. **Entity Relations**
   - How does it relate to other entities?
   - Example: User has many Orders

10. **Infrastructure**
    - Which tables?
    - How to manage data?
    - Legacy considerations?

### Important:

**ONLY start coding after completing this design**

Don't skip this step for complex domains!

---

## Migrations

### Rules:
- Write clear, reversible migrations
- Document what migration does

### Example:
```
Migration CreateUsersTable:
    function up():
        createTable('users', table =>
            table.id()
            table.string('name')
            table.string('email')
            table.string('phone').nullable()
            table.foreignId('organization_id').constrained()
            table.integer('type')
            table.boolean('is_special').default(false)
            table.timestamps()
        )

    function down():
        dropTable('users')
```

---

**See also:**
- [Application Layer](application-layer.md) - Queries and Commands using repositories
- [Code Quality](code-quality.md) - SOLID principles for entities
- [Architecture](architecture.md) - DDD structure
- [Critical Rules](critical-rules.md) - Performance and best practices
