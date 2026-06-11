# Frontend-Backend Integration

**MANDATORY: Read this before implementing any feature that involves both frontend and backend.**

---

## The Problem

When frontend and backend are developed without coordination:
- Different field names (`full_name` vs `first_name` + `last_name`)
- Different data formats (form-urlencoded vs JSON)
- Different response structures (`{token}` vs `{user, tokens}`)
- Unhandled error cases (expecting 422, getting 500)

**Result:** Hours of debugging, frustration, and broken features.

---

## Development Strategies

There are three main strategies for developing frontend-backend features:

| Strategy | When to Use | Pros | Cons |
|----------|-------------|------|------|
| **Backend First** | Backend team available, clear requirements | Real API, no mocks to maintain | Frontend blocked until API ready |
| **Frontend First** | UI/UX priority, backend unclear | Fast user feedback | Backend may not match |
| **Parallel** | Time pressure, both teams available | Fastest delivery | Requires mocks, more coordination |

---

## Strategy 1: Backend First (Recommended)

**Best for:** Most features, especially when requirements are clear.

### Flow

```
1. Define API Contract ────────────────────────────────────┐
                                                           │
2. Write Contract Tests (failing) ─────────────────────────┤
                                                           │
3. Implement Backend ──────────────────────────────────────┤
   └── Contract tests pass                                 │
                                                           │
4. Implement Frontend ─────────────────────────────────────┤
   └── Uses real API (no mocks)                            │
                                                           │
5. Integration Testing ────────────────────────────────────┘
```

### Advantages
- Frontend works against real API from day one
- No mock maintenance
- Fewer integration surprises
- Contract tests catch issues early

### Disadvantages
- Frontend development blocked until backend ready
- May delay user feedback on UI

### When to Choose
- Clear, stable requirements
- Backend team has capacity
- Feature is backend-heavy (complex business logic)

---

## Strategy 2: Frontend First

**Best for:** UI/UX exploration, when backend approach is unclear.

### Flow

```
1. Define API Contract (draft) ────────────────────────────┐
   └── Based on UI needs                                   │
                                                           │
2. Implement Frontend with Mocks ──────────────────────────┤
   └── Mock server returns contract responses              │
                                                           │
3. Validate UI/UX with stakeholders ───────────────────────┤
   └── May iterate on contract                             │
                                                           │
4. Finalize API Contract ──────────────────────────────────┤
                                                           │
5. Write Contract Tests ───────────────────────────────────┤
                                                           │
6. Implement Backend ──────────────────────────────────────┤
                                                           │
7. Switch Frontend to Real API ────────────────────────────┤
                                                           │
8. Integration Testing ────────────────────────────────────┘
```

### Advantages
- Fast UI/UX iteration
- User feedback before backend investment
- Frontend defines what data it needs

### Disadvantages
- Mocks must be maintained
- Risk of backend not matching
- Two integration phases (mock → real)

### When to Choose
- UI/UX is primary concern
- Backend approach uncertain
- Need early stakeholder feedback

---

## Strategy 3: Parallel Development

**Best for:** Tight deadlines, both teams available.

### Flow

```
                    1. Define API Contract
                              │
              ┌───────────────┴───────────────┐
              │                               │
              ▼                               ▼
    2a. Backend Development          2b. Frontend Development
        │                                    │
        ├── Contract tests                   ├── Mock server
        ├── Implementation                   ├── Implementation
        └── Unit tests                       └── Component tests
              │                               │
              └───────────────┬───────────────┘
                              │
                              ▼
                    3. Integration Testing
                              │
                              ▼
                    4. Contract Tests Pass
```

### Requirements for Parallel Development

#### A. Shared API Contract (Source of Truth)

Both teams MUST agree on the contract BEFORE starting:

```
/docs/api-contracts/
  feature-name/
    endpoint-1.md   # Agreed contract
    endpoint-2.md
```

#### B. Mock Server for Frontend

Frontend uses a mock server that implements the contract:

**Option 1: MSW (Mock Service Worker)** - Recommended for React/Vue
```typescript
// mocks/handlers.ts
import { http, HttpResponse } from 'msw'

export const handlers = [
  http.post('/api/v1/auth/register', () => {
    return HttpResponse.json({
      user: { id: '01HQ...', email: 'user@example.com' },
      tokens: { access_token: 'mock-jwt', refresh_token: 'mock-refresh' }
    }, { status: 201 })
  }),

  http.post('/api/v1/auth/login', () => {
    return HttpResponse.json({
      user: { id: '01HQ...', email: 'user@example.com' },
      tokens: { access_token: 'mock-jwt', refresh_token: 'mock-refresh' }
    })
  }),
]
```

**Option 2: JSON Server** - Quick and simple
```json
// db.json
{
  "users": [
    { "id": "1", "email": "user@example.com", "name": "Test User" }
  ]
}
```

**Option 3: Prism** - OpenAPI-based mocking
```bash
prism mock openapi.yaml
```

#### C. Contract Tests for Backend

Backend writes contract tests that verify the exact format:

```python
class TestFrontendAuthContract:
    """Contract tests - DO NOT change without frontend coordination."""

    def test_register_returns_expected_structure(self, client):
        response = client.post('/api/v1/auth/register', json={
            'full_name': 'Test User',
            'email': 'test@example.com',
            'password': 'SecurePass123!',
            'organization_name': 'Test Co'
        })

        assert response.status_code == 201
        data = response.json()

        # Verify exact structure frontend expects
        assert 'user' in data
        assert 'tokens' in data
        assert 'access_token' in data['tokens']
        assert 'refresh_token' in data['tokens']
```

### Advantages
- Fastest total delivery time
- Teams unblocked
- Early parallel testing

### Disadvantages
- Requires discipline and communication
- Mock maintenance overhead
- Integration issues discovered later
- Contract changes require coordination

### When to Choose
- Tight deadline
- Both teams available
- Clear, stable requirements
- Good team communication

---

## CONTRACT-FIRST Development (All Strategies)

Regardless of strategy, the API contract must be defined first.

### Step 1: Create Contract Document

Location: `/docs/api-contracts/{feature}/{endpoint}.md`

```markdown
# POST /api/v1/orders

## Request

### Headers
| Header | Value | Required |
|--------|-------|----------|
| Content-Type | application/json | Yes |
| Authorization | Bearer {token} | Yes |

### Body
```json
{
  "product_id": "string (ULID, required)",
  "quantity": "integer (min: 1, required)",
  "notes": "string (optional, max: 500)"
}
```

## Response

### Success (201 Created)
```json
{
  "order": {
    "id": "01HQ...",
    "product_id": "01HQ...",
    "quantity": 5,
    "status": "pending",
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

### Error Responses
| Status | Condition | Response |
|--------|-----------|----------|
| 400 | Invalid product_id | `{"detail": "Invalid product ID format"}` |
| 404 | Product not found | `{"detail": "Product not found"}` |
| 422 | Quantity < 1 | `{"detail": "Quantity must be at least 1"}` |
| 401 | Missing/invalid token | `{"detail": "Not authenticated"}` |
```

### Step 2: Review Contract

- Backend and frontend developers review
- Resolve any ambiguities
- Agree on field names, types, formats
- List ALL error cases

### Step 3: Both Teams Implement Against Contract

- **Backend:** Contract tests first, then implementation
- **Frontend:** Implementation against mocks or real API

---

## Mock Strategies for Parallel Development

### When to Use Mocks

| Scenario | Mock Strategy |
|----------|---------------|
| Local development | MSW (browser-level) |
| CI/CD pipeline | MSW or test fixtures |
| API not ready | JSON Server or Prism |
| Complex scenarios | Custom mock server |

### MSW Setup (Recommended)

```typescript
// src/mocks/browser.ts
import { setupWorker } from 'msw/browser'
import { handlers } from './handlers'

export const worker = setupWorker(...handlers)
```

```typescript
// src/main.tsx (development only)
async function enableMocking() {
  if (process.env.NODE_ENV === 'development' && process.env.USE_MOCKS) {
    const { worker } = await import('./mocks/browser')
    return worker.start()
  }
}

enableMocking().then(() => {
  ReactDOM.render(<App />, document.getElementById('root'))
})
```

### Mock Maintenance Rules

1. **Mocks MUST match contract** - Update mocks when contract changes
2. **Test with real API before merge** - Never merge without integration test
3. **Remove mocks when API ready** - Don't keep stale mocks
4. **Version mocks with contract** - Same PR updates both

---

## Contract Tests

### What Contract Tests Verify

1. **Request format:** Exact payload structure
2. **Response structure:** Exact JSON structure
3. **Error responses:** All error cases with correct status
4. **Headers:** Content-Type, Authorization format

### Contract Test Location

```
tests/
  integration/
    contracts/
      test_frontend_auth_contract.py
      test_frontend_orders_contract.py
```

### Contract Test Naming

```python
class TestFrontend{Feature}Contract:
    """
    Contract tests for {feature}.

    CONTRACT: /docs/api-contracts/{feature}/

    WARNING: Do not change without frontend coordination.
    """

    def test_{action}_with_frontend_payload_should_succeed(self):
        """Frontend sends THIS EXACT payload."""
        pass

    def test_{action}_error_returns_expected_format(self):
        """Frontend expects THIS EXACT error format."""
        pass
```

---

## Common Pitfalls and Solutions

### Pitfall 1: Different Field Names

**Problem:**
```
Backend: first_name, last_name
Frontend: full_name
```

**Solution:** Contract defines ONE format. Backend adapts with validator:

```python
@model_validator(mode='after')
def parse_full_name(self):
    if self.full_name and not self.first_name:
        parts = self.full_name.split(' ', 1)
        self.first_name = parts[0]
        self.last_name = parts[1] if len(parts) > 1 else ''
    return self
```

### Pitfall 2: Different Content Types

**Problem:**
```
Backend: expects application/json
Frontend: sends application/x-www-form-urlencoded
```

**Solution:** Contract specifies Content-Type. Backend supports what frontend sends:

```python
async def get_login_credentials(
    request: Request,
    username: Optional[str] = Form(None),
    password: Optional[str] = Form(None),
) -> LoginRequest:
    content_type = request.headers.get('content-type', '')

    if 'application/x-www-form-urlencoded' in content_type:
        return LoginRequest(email=username, password=password)

    # Fall back to JSON
    body = await request.json()
    return LoginRequest(**body)
```

### Pitfall 3: Different Response Structures

**Problem:**
```
Backend returns: {access_token: "..."}
Frontend expects: {tokens: {access_token: "..."}}
```

**Solution:** Contract defines exact structure. Both sides follow it.

### Pitfall 4: Unhandled Errors

**Problem:**
```
Backend: throws WeakPasswordException → 500 Internal Server Error
Frontend: expects 422 with message
```

**Solution:** Router catches ALL domain exceptions:

```python
@router.post('/register')
async def register(request: RegisterRequest):
    try:
        return controller.register(request)
    except UserAlreadyExistsException as e:
        raise HTTPException(status_code=409, detail=str(e))
    except WeakPasswordException as e:
        raise HTTPException(status_code=422, detail=str(e))
```

---

## Integration Checklist

### Before Starting (All Strategies)

- [ ] API contract document created
- [ ] Contract reviewed by frontend AND backend
- [ ] Error cases documented
- [ ] Field names and types agreed

### Backend First

- [ ] Contract tests written (failing)
- [ ] Backend implemented
- [ ] Contract tests pass
- [ ] Frontend implements against real API

### Frontend First

- [ ] Mock server set up
- [ ] Frontend implements against mocks
- [ ] UI/UX validated
- [ ] Contract finalized
- [ ] Contract tests written
- [ ] Backend implements
- [ ] Frontend switches to real API

### Parallel Development

- [ ] Contract agreed and locked
- [ ] Mock handlers match contract
- [ ] Backend contract tests pass
- [ ] Frontend works with mocks
- [ ] Integration test with real API
- [ ] Mocks removed/disabled

### Before Merge

- [ ] `make test-contract` passes
- [ ] Manual E2E test performed
- [ ] Contract document updated if changed

---

## Quick Reference: Development Strategy Decision Tree

```
                    ┌─────────────────────────────────┐
                    │ Is the API contract clear?      │
                    └─────────────┬───────────────────┘
                                  │
                    ┌─────────────┴───────────────┐
                    │                             │
                   YES                            NO
                    │                             │
                    ▼                             ▼
    ┌───────────────────────────┐   ┌───────────────────────────┐
    │ Is there time pressure?   │   │ Use FRONTEND FIRST        │
    └───────────┬───────────────┘   │ - Explore UI/UX           │
                │                   │ - Define contract from UI  │
    ┌───────────┴───────────┐       │ - Then backend            │
    │                       │       └───────────────────────────┘
   YES                      NO
    │                       │
    ▼                       ▼
┌───────────────────┐   ┌───────────────────────────┐
│ Are both teams    │   │ Use BACKEND FIRST         │
│ available?        │   │ - Safest approach         │
└─────────┬─────────┘   │ - No mocks needed         │
          │             │ - Clear integration       │
    ┌─────┴─────┐       └───────────────────────────┘
    │           │
   YES          NO
    │           │
    ▼           ▼
┌───────────┐ ┌───────────────────────────┐
│ PARALLEL  │ │ BACKEND FIRST (with       │
│ - Mocks   │ │ frontend starting later)  │
│ - Fast    │ └───────────────────────────┘
└───────────┘
```

---

**See also:**
- [Development Workflow](development-workflow.md) - Backend implementation phases
- [Critical Rules](critical-rules.md) - Must-follow rules
- [HTTP Layer Patterns](http-layer-patterns.md) - API patterns
