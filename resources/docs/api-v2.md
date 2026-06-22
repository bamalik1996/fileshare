# AirToShare API v2

Authenticate with `Authorization: Bearer {api_key}` on all `/api/v2/*` routes except API-key management.

## Shares

- `POST /api/v2/shares` — create share
- `GET /api/v2/shares` — list active shares
- `GET /api/v2/shares/{uuid}` — show share

## API keys (session login required)

- `POST /api/v2/api-keys` — create key (plaintext returned once)
- `DELETE /api/v2/api-keys/{id}` — revoke key

## Response envelope

```json
{ "status": "success", "data": { ... } }
{ "status": "error", "message": "...", "errors": {} }
```

Rate limit: 60 requests per minute per API key.
