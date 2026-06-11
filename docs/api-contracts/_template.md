# {METHOD} /api/v1/{path}

{Brief description of what this endpoint does.}

## Request

### Headers

| Header | Value | Required |
|--------|-------|----------|
| Content-Type | application/json | Yes |
| Authorization | Bearer {token} | Yes/No |

### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| id | string (ULID) | Resource identifier |

### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| limit | integer | No | 20 | Max items to return |
| offset | integer | No | 0 | Items to skip |

### Body

```json
{
  "field_name": "string (required) - Description",
  "optional_field": "string (optional) - Description",
  "nested_object": {
    "sub_field": "integer (required, min: 1) - Description"
  }
}
```

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| field_name | string | Yes | max: 255 | Description |
| optional_field | string | No | max: 500 | Description |

## Response

### Success ({STATUS_CODE} {STATUS_TEXT})

```json
{
  "resource_name": {
    "id": "01HQ...",
    "field": "value",
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

### Error Responses

| Status | Condition | Response Body |
|--------|-----------|---------------|
| 400 | Invalid input format | `{"detail": "Invalid field format"}` |
| 401 | Not authenticated | `{"detail": "Not authenticated"}` |
| 403 | Not authorized | `{"detail": "Not authorized to access this resource"}` |
| 404 | Resource not found | `{"detail": "Resource not found"}` |
| 409 | Conflict (duplicate) | `{"detail": "Resource already exists"}` |
| 422 | Validation error | `{"detail": "Validation error message"}` |

## Frontend Implementation

```typescript
// Request
const response = await apiClient.{method}('/api/v1/{path}', {
  field_name: data.fieldName,
  optional_field: data.optionalField,
})

// Response handling
const { resource_name } = response.data

// Error handling
try {
  // ... request
} catch (error) {
  if (error.response?.status === 422) {
    setError(error.response.data.detail)
  }
}
```

## Backend Implementation Notes

- Domain exceptions to catch: `{Exception1}`, `{Exception2}`
- Required permissions: `{permission}`
- Rate limiting: {limit} requests per {period}

## Contract Tests

Location: `tests/integration/contracts/test_frontend_{feature}_contract.py`

Test cases:
- `test_{action}_with_valid_payload_should_succeed`
- `test_{action}_with_invalid_field_returns_422`
- `test_{action}_without_auth_returns_401`

## Changelog

| Date | Change | Author |
|------|--------|--------|
| YYYY-MM-DD | Initial contract | Name |
