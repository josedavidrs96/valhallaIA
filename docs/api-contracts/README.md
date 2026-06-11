# API Contracts

This directory contains the API contracts that define the exact format of requests and responses between frontend and backend.

## Purpose

API contracts are the **source of truth** for frontend-backend communication. They:
- Define exact request/response formats
- List all error cases
- Prevent mismatches between frontend and backend
- Enable parallel development with accurate mocks

## Directory Structure

```
api-contracts/
  README.md           # This file
  _template.md        # Template for new contracts
  auth/               # Authentication endpoints
    login.md
    register.md
    logout.md
  orders/             # Order management endpoints
    create-order.md
    get-order.md
    list-orders.md
  users/              # User management endpoints
    get-me.md
    update-profile.md
```

## Creating a New Contract

1. Copy `_template.md` to the appropriate subdirectory
2. Fill in all sections
3. Have both frontend AND backend review
4. Create contract tests before implementing

## Contract File Format

Each contract file must include:

1. **Endpoint** - Method and path
2. **Request** - Headers, body format, validation rules
3. **Response** - Success response structure
4. **Error Responses** - All possible errors with status codes
5. **Frontend Implementation** - Example code
6. **Contract Tests** - Reference to test file

## Rules

1. **Contract BEFORE code** - Never implement without a contract
2. **Both sides review** - Frontend AND backend must agree
3. **Contract tests required** - Tests verify the contract
4. **Update when changing** - Any API change updates the contract first
5. **Version if breaking** - Breaking changes require new version

## Related Documentation

- [Frontend-Backend Integration](/ai_docs/architecture/frontend-backend-integration.md)
- [Critical Rules](/ai_docs/architecture/critical-rules.md)
- [Development Workflow](/ai_docs/architecture/development-workflow.md)
