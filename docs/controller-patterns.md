# Controller Architecture

## Thin Controller Pattern

Controllers should remain **thin**, delegating to services for business logic.

✅ **DO**: Thin controller with service injection
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GoogleOAuthService;
use App\Http\Requests\GoogleAuthTokenRequest;
use Illuminate\Http\JsonResponse;

class GoogleAuthController extends Controller
{
    public function __construct(
        protected GoogleOAuthService $oauthService
    ) {}

    public function authenticateToken(GoogleAuthTokenRequest $request): JsonResponse
    {
        $input = $request->validated();

        try {
            // Delegate business logic to service
            $user = $this->oauthService->authenticateWithIdToken($input['id_token']);

            // Generate token (controller responsibility)
            $token = $user->createToken($request->input('device_name', 'api_client'))->plainTextToken;

            // Format response (controller responsibility)
            return response()->json(['token' => $token]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Google authentication failed',
                'error' => $e->getMessage()
            ], 401);
        }
    }
}
```

❌ **DON'T**: Fat controller with business logic
```php
public function authenticateToken(Request $request): JsonResponse
{
    // ❌ Validation in controller (should be FormRequest)
    $request->validate(['id_token' => 'required|string']);

    // ❌ Business logic in controller (should be service)
    $client = new Google_Client();
    $client->setClientId(config('services.google.client_id'));
    $payload = $client->verifyIdToken($request->id_token);

    if (!$payload) {
        return response()->json(['error' => 'Invalid token'], 401);
    }

    // ❌ More business logic (should be service)
    $user = User::where('google_id', $payload['sub'])->first();
    if (!$user) {
        $user = User::where('email', $payload['email'])->first();
        if ($user) {
            $user->update(['google_id' => $payload['sub']]);
        } else {
            $user = User::create([...]);
        }
    }

    $token = $user->createToken('api_client')->plainTextToken;
    return response()->json(['token' => $token]);
}
```

## Controller Responsibilities

**Controllers SHOULD:**
- ✅ Accept validated requests (FormRequest)
- ✅ Delegate to services for business logic
- ✅ Format responses consistently
- ✅ Handle HTTP-level concerns (status codes, headers)

**Controllers SHOULD NOT:**
- ❌ Contain complex business logic
- ❌ Directly manipulate multiple models
- ❌ Perform validation (use FormRequest)
- ❌ Handle authorization inline (use Gate/Policy)

## Constructor Injection

**Pattern**: Inject services via constructor

```php
public function __construct(
    protected GoogleOAuthService $oauthService
) {}
```

**Benefits:**
- Type safety
- Automatic dependency resolution
- Easy testing (can mock services)
- Clear dependencies
