# Code Quality & Best Practices

SOLID principles, naming conventions, enums, and domain layer best practices.

## SOLID Principles

### Single Responsibility Principle (SRP)

**Rule:** A class should solve ONE problem (not do one thing)

#### Understanding SRP

**Common Misunderstanding:**
"A class should only have one method"

**Correct Understanding:**
"A class should focus on solving one problem"

#### Example:

```
// CORRECT - One responsibility: User repository
class UserRepository:
    function findById(id: UserId): ?User
    function findByEmail(email: string): ?User
    function findByOrganization(id: OrganizationId): User[]
    function save(user: User): void
    // Multiple methods, but ONE responsibility: User data access
```

#### Large Files with Single Responsibility

**Problem:** File is huge but has single responsibility

**Solution:** Split into sub-responsibilities

```
// Before: UserRepository (2000 lines)
class UserRepository:
    function findById()
    function findByEmail()
    function findWithStats()
    function findWithOrders()
    // ... 50 more methods

// After: Split by sub-responsibility
// UserRepository (core methods)
class UserRepository:
    function findById()
    function save()

// UserRMQueryRepository (read models)
class UserRMQueryRepository:
    function findWithStats()
    function findWithOrders()
```

### Dependency Principle - "Ask Only What You Need"

**Rule:** A method should only ask for what it actually needs.

#### Bad Example:
```
function sendEmail(user: User):
    email = user.getEmail()
    mailer.send(email, 'Welcome!')
    // Only needs email, but coupled to entire User object

// Problems:
// - Can't reuse for non-User emails
// - Hard to test (need full User mock)
// - Breaks when User changes
```

#### Good Example:
```
function sendEmail(email: string):
    mailer.send(email, 'Welcome!')
    // Only asks for what it needs

// Benefits:
// - Reusable for any email
// - Easy to test (just pass string)
// - Not affected by User changes
```

#### When to Make Exceptions

```
// Question: Will I need more user data tomorrow?

// If YES and certain:
function sendWelcomeEmail(user: User):
    // Acceptable if you know you'll need name, preferences, etc.

// If NO or uncertain:
function sendWelcomeEmail(email: string, name: string):
    // Better - only ask for what you need
```

**Video resource:** https://www.youtube.com/watch?v=ci12akiGg1s&t=362s&ab_channel=ProductCrafter

Also covers "Tell Don't Ask" pattern.

### Stateless Business Services

**Rule:** Services should NEVER store state

#### Correct Pattern:
```
class OrderPriceCalculator:
    constructor(
        readonly taxService: TaxService  // Readonly, no state
    )

    function calculate(order: Order): Money
        // No state stored, just computation
        return taxService.applyTax(order.basePrice())
```

#### Wrong Pattern - Services with State (1990s anti-pattern):
```
class Calculator:
    private result: int = 0  // STATE!

    function add(n: int): void
        this.result += n

    function subtract(n: int): void
        this.result -= n

    function getResult(): int
        return this.result

// Problems:
// - Multiple entry points with shared state
// - Unpredictable results depending on call order
// - Not thread-safe
// - Hard to test
```

#### Exception: Single Entry Point with State

```
class ReportGenerator:
    private data: array = []  // State OK if...

    function invoke(request: ReportRequest): Report
        // ... only ONE entry point (invoke)
        this.data = this.fetchData(request)
        return this.generateReport()

    // Private methods can use this.data
    private function fetchData()
    private function generateReport()

// Acceptable because:
// - Only one entry point (invoke)
// - State is predictable (always starts fresh)
// - Can't have inconsistent state
```

**Note:** Don't confuse with utility/helper classes (those have no state at all).

---

## Domain Layer Best Practices

### Anemic Entities - What Goes Where?

#### Business Logic in Entities

**Should be in entity:**
- Validate data and maintain invariants
- Business relationships within the domain
- Calculations and business rules specific to entity

```
class User:
    function changeEmail(newEmail: string): void
        // Invariant: Email must be valid
        if (!isValidEmail(newEmail)):
            throw InvalidEmailException(newEmail)

        this.email = newEmail
        this.recordEvent(new UserEmailChangedEvent(this.id, newEmail))

    function getFullName(): string
        // Calculation within entity
        return this.name + ' ' + this.surname

    function isSpecial(): bool
        // Business rule specific to user
        return this.type === UserType.VIP
```

#### Business Logic in Domain Services

**Should be in domain service:**
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
        datetime: DateTime
    ): bool
        // Needs repository - can't be in entity
        existingOrder = repository.findByOrganizationAndDatetime(
            organizationId,
            datetime
        )

        return existingOrder === null

class RelateUsersInGroupService:
    function relateUsers(user1: User, user2: User): void
        // Relates TWO different user instances
        // Can't be in User entity (doesn't know about other instances)
```


---

## Naming Conventions

### Semantic Naming

#### Use: Consistent terminology
```
class Order
class OrderRepository
class CreateOrderCommand
```

#### Avoid:
- Inconsistent naming across the codebase
- Generic names like "Manager", "Helper" (unless truly generic)

### "Comprove" is Not English

#### Don't use: `Comprove`
```
function comproveOrder()  // Not English!
```

#### Replace with proper English:
```
function validateOrder()  // Check rules
function checkOrder()     // Verify existence
function ensureOrder()    // Make certain
```

**Context determines which word:**
- `Validate` - Check business rules
- `Check` - Verify condition
- `Ensure` - Guarantee something is true

---

## Enums

### Allowed Functionality in Enums

In theory, enums should only have constants. In practice, we allow some functionality for simplicity.

### 1. Decoration (Translations)

```
enum UserType:
    REGULAR = 'regular'
    VIP = 'vip'
    CORPORATE = 'corporate'

    function getLabel(language: string): string
        match this:
            REGULAR => language === 'es' ? 'Regular' : 'Regular'
            VIP => language === 'es' ? 'VIP' : 'VIP'
            CORPORATE => language === 'es' ? 'Corporativo' : 'Corporate'

    function getLabels(language: string): array
        return cases().map(type => type.getLabel(language))
```

### 2. Mini Business Rules

```
enum UserType:
    REGULAR = 'regular'
    VIP = 'vip'
    CORPORATE = 'corporate'

    function isSpecialUser(): bool
        // Business rule closely tied to enum values
        return this === VIP || this === CORPORATE

    function getDiscountPercentage(): int
        match this:
            VIP => 20
            CORPORATE => 15
            REGULAR => 0
```

**Reasoning:** Business logic is directly related to the enum values.

### 3. Mappings/Translations Between Systems

```
enum ExternalPaymentStatus:
    AUTHORIZED = 'authorized'
    CANCELLED = 'cancelled'
    ERROR = 'error'

    function toInternalStatus(): PaymentStatus
        // Map external system enum to internal enum
        match this:
            AUTHORIZED => PaymentStatus.PAID
            CANCELLED => PaymentStatus.CANCELLED
            ERROR => PaymentStatus.FAILED

enum OrderSource:
    WEB = 'web'
    PHONE = 'phone'
    WIDGET = 'widget'

    function toAnalyticsCode(): string
        match this:
            WEB => 'WB'
            PHONE => 'PH'
            WIDGET => 'WG'
```

### When NOT to Add to Enum

**Don't add if logic is complex or depends on external data:**

```
// BAD - Too complex for enum
enum UserType:
    VIP = 'vip'

    function calculateLoyaltyPoints(
        order: Order,
        rules: LoyaltyRules
    ): int
        // Too complex - belongs in service

// GOOD - Move to service
class LoyaltyPointsCalculator:
    function calculate(
        user: User,
        order: Order
    ): int
        // Complex logic here
```

---

## Communication Between Domains (within same BC)

### Preferred: Query and Command Bus

```
class OrderService:
    constructor(
        queryBus: QueryBus,
        commandBus: CommandBus
    )

    function createOrder(dto: CreateOrderDTO): OrderId
        // Get user via Query Bus
        user = queryBus.query(
            new GetUserByIdQuery(dto.userId)
        )

        // Validate
        // ...

        // Create order via Command Bus
        return commandBus.dispatch(
            new CreateOrderCommand(dto)
        )
```

### Exception: Very Closely Related Domains

**When domains are VERY, VERY closely related** (ideally same module):

```
class OrderService:
    constructor(
        userRepository: UserRepository,
        orderRepository: OrderRepository
    )

    function createOrder(dto: CreateOrderDTO): OrderId
        // Direct repository access OK if domains very closely related
        user = userRepository.findById(dto.userId)
        // ...
```

**Rule of thumb:** If in doubt, use Query/Command Bus.

---

**See also:**
- [Architecture](architecture.md) - DDD structure
- [Application Layer](application-layer.md) - Queries and Commands
- [Infrastructure](infrastructure.md) - Repositories and Entities
- [Critical Rules](critical-rules.md) - Utility classes
