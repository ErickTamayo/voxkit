# Authentication

## Overview

The backend supports two authentication flows:
1. **Email code (GraphQL)** — default sign-in/sign-up for token and session flows
2. **Google OAuth (REST)** — token and session flows via Socialite

Timestamps in GraphQL authentication payloads use the backend `Timestamp` scalar (Unix milliseconds in UTC).

---

## Email Code Authentication (GraphQL)

**Mutations**:
- `signUpAndRequestAuthenticationToken`
- `requestAuthenticationCode`
- `authenticateWithCode`
- `revokeToken`

**Flow Summary**:
- `signUpAndRequestAuthenticationToken` creates the user (if needed) and emails a code.
- `requestAuthenticationCode` emails a code for existing users (silent if not found).
- `authenticateWithCode` verifies the code and returns a token (TOKEN) or establishes a session (SESSION).

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

**Rules**:
- 6-digit numeric code
- 10-minute TTL
- One-time use
- Code is invalidated after 5 failed verification attempts
- Verification is rate-limited on three scopes:
  - 10 failed attempts per 10 minutes per email+IP
  - 8 failed attempts per 10 minutes per email (cross-IP protection)
  - 25 failed attempts per 10 minutes per IP (credential-spray protection)
- Code request mutations are rate-limited on four scopes:
  - 60-second cooldown between sends per email
  - 5 requests per 10 minutes per email+IP
  - 8 requests per hour per email (cross-IP protection)
  - 20 requests per 10 minutes per IP
- `mode` determines token vs session behavior
- `mode` values: `TOKEN` or `SESSION`
- `mode` is only required for `authenticateWithCode`
- Successful verification sets `email_verified_at`
- Registration blocks disposable email domains via `indisposable`
- Registration with an existing email stays silent and still sends a code
- Sign-in requests stay silent even if the email does not exist
- Session verification relies on the GraphQL route session middleware (see `config/lighthouse.php`)
- Input validation is handled by Lighthouse `@rules` on the GraphQL input fields (validation errors surface as GraphQL errors)
- Throttled requests return GraphQL error union responses with retry timing guidance
- Rate-limit violations and lockouts are logged as security warnings with hashed email identifiers
- In local dev, issued auth codes are logged to the console for convenience

**Example (token flow)**:
```graphql
mutation SignUp($input: SignUpAndRequestAuthenticationTokenInput!) {
  signUpAndRequestAuthenticationToken(input: $input) {
    __typename
    ... on SignUpAndRequestAuthenticationTokenOkResponse { ok }
    ... on SignUpAndRequestAuthenticationTokenErrorResponse { message }
  }
}
```

Variables:
```json
{
  "input": {
    "name": "Ada Lovelace",
    "email": "ada@example.com"
  }
}
```
```graphql
mutation Authenticate($input: AuthenticateWithCodeInput!) {
  authenticateWithCode(input: $input) {
    __typename
    ... on AuthenticateWithCodeTokenResponse { token }
    ... on AuthenticateWithCodeErrorResponse { message }
  }
}
```

Variables:
```json
{
  "input": {
    "email": "ada@example.com",
    "code": "123456",
    "mode": "TOKEN",
    "device_name": "ios"
  }
}
```

---

## Google OAuth Authentication (REST)

### Token-Based

**Route**: `POST /api/auth/google/token`

**Controller**: `app/Http/Controllers/GoogleAuthController.php`

**Request Flow**:
1. Client sends ID token from Google Sign-In SDK
2. Backend verifies token with Google API
3. Backend finds or creates user
4. Backend generates Sanctum personal access token
5. Client stores token in secure storage

### Session-Based

**Routes**:
- `GET /auth/google/redirect` - Redirect to Google
- `GET /auth/google/callback` - Handle OAuth callback

**Request Flow**:
1. Client redirects to `/auth/google/redirect`
2. User authenticates with Google
3. Google redirects back to `/auth/google/callback`
4. Backend authenticates user via Laravel session
5. Backend redirects to configured callback URL

---

## Protected Routes

**Pattern**: Use `auth:sanctum` middleware

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/ping', [AuthController::class, 'ping']);
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

**Accessing Authenticated User:**
```php
use Illuminate\Support\Facades\Auth;

$user = Auth::user(); // Returns User model or null
$userId = Auth::id(); // Returns user ID or null
```
