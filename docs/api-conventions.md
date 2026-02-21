# API Structure and Routing

## Route Organization

**Public Routes** (`routes/api.php`):
```php
// Authentication (Google OAuth remains REST)
Route::post('/auth/google/token', [GoogleAuthController::class, 'authenticateToken']);

// OAuth (web routes)
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
```

## Authentication (GraphQL)

Email code authentication is GraphQL-only. Mutations return a typed union for easier client handling.
The `mode` input determines whether the flow issues a token or establishes a session.
The `mode` input is only required for `authenticateWithCode`.
Validation is enforced via Lighthouse `@rules` on input fields; invalid input returns GraphQL validation errors.
`signUpAndRequestAuthenticationToken` and `requestAuthenticationCode` always email a code.

**Response Types**:
```graphql
type SignUpAndRequestAuthenticationTokenOkResponse { ok: Boolean! }
type SignUpAndRequestAuthenticationTokenErrorResponse { message: String! }
union SignUpAndRequestAuthenticationTokenResponse = SignUpAndRequestAuthenticationTokenOkResponse | SignUpAndRequestAuthenticationTokenErrorResponse

type RequestAuthenticationCodeOkResponse { ok: Boolean! }
type RequestAuthenticationCodeErrorResponse { message: String! }
union RequestAuthenticationCodeResponse = RequestAuthenticationCodeOkResponse | RequestAuthenticationCodeErrorResponse

type AuthenticateWithCodeOkResponse { ok: Boolean! }
type AuthenticateWithCodeTokenResponse { token: String! }
type AuthenticateWithCodeErrorResponse { message: String! }
union AuthenticateWithCodeResponse = AuthenticateWithCodeOkResponse | AuthenticateWithCodeTokenResponse | AuthenticateWithCodeErrorResponse

type RevokeTokenOkResponse { ok: Boolean! }
type RevokeTokenErrorResponse { message: String! }
union RevokeTokenResponse = RevokeTokenOkResponse | RevokeTokenErrorResponse
```

**Core Mutations**:
```graphql
mutation($input: SignUpAndRequestAuthenticationTokenInput!) {
  signUpAndRequestAuthenticationToken(input: $input) {
    __typename
    ... on SignUpAndRequestAuthenticationTokenOkResponse { ok }
    ... on SignUpAndRequestAuthenticationTokenErrorResponse { message }
  }
}
```

Example variables:
```json
{
  "input": {
    "name": "Ada Lovelace",
    "email": "ada@example.com"
  }
}
```

**Protected Routes** (require `auth:sanctum`):
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/ping', [AuthController::class, 'ping']);

    // Media uploads
    Route::post('/media', [MediaController::class, 'store']);
});
```

**GraphQL Logout**:
```graphql
mutation {
  revokeToken {
    ok
  }
}
```

## Response Patterns

**Flexible Response Format** (approved standard):

The backend uses flexible response formats appropriate to each endpoint. Common patterns:

**Token Response:**
```php
return response()->json(['token' => $token], 201);
```

**Data Response:**
```php
return response()->json(['data' => $data], 200);
```


**No Content Response:**
```php
return response()->noContent(); // 204
```

**Error Response (validation):**
```php
// Laravel auto-formats validation errors as:
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

## HTTP Status Codes

Standard codes used across the API:

- **200 OK**: Successful GET/POST/PUT
- **201 Created**: Resource created successfully
- **204 No Content**: Successful operation with no response body (ping)
- **401 Unauthorized**: Invalid credentials, expired token
- **403 Forbidden**: Authenticated but not authorized
- **404 Not Found**: Resource doesn't exist
- **422 Unprocessable Entity**: Validation failure

## GraphQL Timestamp Format

**Scalar Type**: `Timestamp`
**Format**: Unix milliseconds (integer)
**Timezone**: UTC

All GraphQL timestamp fields use the `Timestamp` scalar type, which serializes Carbon instances to Unix milliseconds for consistency with JavaScript's `Date.now()` and the Temporal API.

**Example Response:**
```json
{
  "created_at": 1768361745000,
  "updated_at": 1768361745000
}
```

**Example Input:**
```graphql
mutation {
  createExpense(input: {
    description: "Studio rental"
    amount: 12500
    currency: "USD"
    category: STUDIO
    date: 1768361745000
  }) {
    id
    date
  }
}
```

**Backend Handling:**
- **Serialize (DB → GraphQL)**: `$carbon->timestamp * 1000`
- **Parse (GraphQL → DB)**: `Carbon::createFromTimestamp($milliseconds / 1000, 'UTC')`

**Type Safety**: GraphQL schema enforces integer type; non-numeric values are rejected.

## GraphQL Conventions

**File Structure**:
- `schema.graphql`: Main entry point (imports others).
- `graphql/`: Directory for split schema files.
- `graphql/*.graphql`: Domain-specific schema files (e.g., `user.graphql`, `invoice.graphql`).

**Naming**:
- **Inputs**: `*Input` (e.g., `UpdateUserInput`, `SnoozeActivityInput`).
- **Directives**: Use Lighthouse directives extensively to avoid boilerplate code.

**Resolvers**:
- Use fully qualified class paths in `@field` directives.
- Example: `@field(resolver: "App\\GraphQL\\Mutations\\ActivityMutations@snooze")`

**Authorization**:
- **Protection**: Use `@guard` on all protected queries/mutations.
- **Policies**: Use `@can`, `@canFind`, or `@canModel` to enforce policies.
  - Pattern for "authorized or null" (concealment): `@canFind(ability: "update", find: "id", action: RETURN_VALUE, returnValue: null)`

**Error Handling**:
- **Auth Mutations**: Use Union types (Ok | Error | Token) to explicitly model success/failure states.
- **Resource Mutations**: Standard GraphQL errors (validation errors via `UnprocessableEntity`).
- **Validation**: Use `@rules` directive on input fields.
  - Example: `email: String! @rules(apply: ["required", "email"])`

**Directives Usage**:
- `@belongsTo`, `@hasOne`, `@hasMany`: Define relationships directly in schema.
- `@morphTo`, `@morphMany`: Handle polymorphic relationships.
- `@paginate`: Simplify list pagination.
- `@whereConditions`: Enable flexible filtering on specific columns.

## Full-Text Search (GraphQL)

The backend exposes user-scoped full-text search through Scout + Typesense.

Query:

```graphql
query Search($query: String, $types: [SearchEntityType!], $first: Int, $page: Int) {
  search(query: $query, types: $types, first: $first, page: $page) {
    data {
      entity {
        __typename
        ... on Contact { id name }
        ... on Client { id type contact { id name } }
        ... on Agent { id agency_name contact { id name } }
        ... on Job { id project_title }
        ... on Invoice { id invoice_number }
        ... on Audition { id project_title }
        ... on Expense { id description }
        ... on Platform { id name }
        ... on Note { id content }
      }
      matches {
        field
        text
        matchedText
        start
        end
        snippet
      }
    }
    paginatorInfo {
      currentPage
      perPage
      total
      lastPage
      hasMorePages
    }
  }
}
```

Notes:
- Results are always limited to the authenticated user's domain.
- `query` is optional. When omitted or empty, search runs in browse mode and returns the most recently updated entities for the selected `types` (or all types when omitted).
- `types` can include one or many entity kinds (for example: `[CLIENT, AGENT]`).
- `types` can be omitted to search across all entity kinds.
- Entity-kind terms are indexed (for example, searching `invoice` can return invoices even when the invoice number does not contain that word).
- `matches` explains why each hit matched, including source field and zero-based `[start, end)` character indexes in `text`.
- `matches` excludes the aggregated `searchable_text` field and only reports concrete indexed source fields.
- Browse mode responses return `matches: []` because there is no query token to explain.
- Relation-derived `matches.field` values are normalized from internal index keys that use `__` separators (for example `client__name` is returned as `client.name`). Models can declare these fields as nested arrays (for example `client => ['name' => ...]`) and the shared search trait handles the conversion.
- `first` is clamped to `1..50`, and `page` is 1-based.

## Nested Relationship Input Pattern

`createJob` and `updateJob` accept relation objects so clients can link existing relationships or create missing related entities in one request.

Each relation object uses one of:
- `id`: link an existing record
- `create`: create the relation inline

Exactly one of `id` or `create` must be provided per relation object.

Create example:

```graphql
mutation CreateJob($input: CreateJobInput!) {
  createJob(input: $input) {
    id
    client_id
    agent_id
    audition_id
  }
}
```

```json
{
  "input": {
    "audition": { "id": "01J..." },
    "client": {
      "create": {
        "contact": {
          "name": "Acme Studios",
          "email": "casting@acme.test",
          "contactable": {
            "client": {
              "type": "COMPANY",
              "industry": "Gaming"
            }
          }
        }
      }
    },
    "agent": {
      "id": "01J..."
    },
    "project_title": "National Campaign",
    "category": "COMMERCIAL",
    "contracted_rate": {
      "amount_cents": 125000,
      "currency": "USD"
    },
    "rate_type": "FLAT",
    "status": "BOOKED"
  }
}
```

The mutation is atomic: related creates and job creation run inside one database transaction.

Update example:

```graphql
mutation UpdateJob($id: ULID!, $input: UpdateJobInput!) {
  updateJob(id: $id, input: $input) {
    id
    client_id
    agent_id
    audition_id
  }
}
```

For updates, optional relationships can be cleared by passing `null`:
- `agent: null`
- `audition: null`

## Agent Mutation Contract (Agent + Contact in One Input)

`createAgent` and `updateAgent` accept a single input that includes a nested `contact` object plus agent fields.

- Contact fields live under `contact` (`name`, `email`, `phone`, address fields, etc.).
- Agent fields stay top-level (`agency_name`, `commission_rate`, `territories`, `is_exclusive`, contract dates).
- `createAgent` requires `name`.
- `updateAgent` applies only provided fields.

Create example:

```graphql
mutation CreateAgent($input: CreateAgentWithContactInput!) {
  createAgent(input: $input) {
    id
    agency_name
    contact {
      id
      name
      email
    }
  }
}
```

```json
{
  "input": {
    "contact": {
      "name": "Jamie Agent",
      "email": "jamie@premier.test"
    },
    "agency_name": "Premier Voices",
    "commission_rate": 1200,
    "is_exclusive": true
  }
}
```

## Pagination Decisions

When adding or changing list fields in GraphQL:

- Decide whether the list should return all items or use pagination.
- Use a full list when domain size is small and bounded (e.g., a job will not have thousands of usage rights).
- If the size is unclear or could grow, default to pagination and ask for confirmation.

## Generic Chart Response Pattern

Time-series charts follow a standardized interface pattern for consistency and reusability.

**Core Types**:
```graphql
# Generic chart data point
type ChartPoint {
  timestamp: Timestamp!  # UTC milliseconds
  value: Int!           # Value in cents (monetary) or count
}

# Wrapper for enum values in unions
type CompactRangeValue {
  value: CompactRange!  # e.g., "1W", "MTD", "QTD"
}

# Union for flexible range specification
union ChartRange = CompactRangeValue | DateRange

# Interface all chart responses must implement
interface ChartResponse {
  range: ChartRange!
  effectiveWindow: DateRangeWindow!
  chart: [ChartPoint!]!
}
```

**Domain-Specific Implementation**:
```graphql
type RevenueChartResponse implements ChartResponse {
  baseCurrency: String!     # Domain-specific field
  range: ChartRange!        # From interface
  effectiveWindow: DateRangeWindow!
  chart: [ChartPoint!]!
}
```

**Benefits**:
- **Reusability**: `ChartPoint` works for any time-series data
- **Type Safety**: Interface ensures consistent structure
- **Flexibility**: Union type supports both enum shortcuts and custom date ranges
- **Extensibility**: New chart types (ExpenseChart, ProfitChart) implement the same interface

**Example Query**:
```graphql
query {
  revenueChart(period: MTD, baseCurrency: "USD") {
    baseCurrency
    range {
      ... on CompactRangeValue { value }
      ... on DateRange { start end }
    }
    effectiveWindow {
      start
      end
      wasExpanded
      daysInRange
    }
    chart {
      timestamp
      value
    }
  }
}
```
