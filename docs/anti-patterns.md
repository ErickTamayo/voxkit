# Anti-Patterns

## ❌ Fat Controllers

**DON'T** put business logic in controllers:

```php
// ❌ BAD
public function store(Request $request): JsonResponse
{
    $validated = $request->validate([...]);

    // Too much logic in controller
    $user = User::where('email', $validated['email'])->first();
    if (!$user) {
        $user = User::create($validated);
        $user->settings()->create([...]);
        Mail::to($user)->send(new WelcomeEmail());
        event(new UserCreated($user));
    }

    return response()->json(['user' => $user], 201);
}
```

**DO** delegate to services:

```php
// ✅ GOOD
public function store(StoreUserRequest $request): JsonResponse
{
    $user = $this->userService->create($request->validated());
    return response()->json(['user' => $user], 201);
}
```

## ❌ N+1 Query Problems

**DON'T** load relationships in loops:

```php
// ❌ BAD (N+1 queries)
$users = User::all();
foreach ($users as $user) {
    echo $user->businessProfile->business_name; // Query per iteration
}
```

**DO** eager load relationships:

```php
// ✅ GOOD (2 queries total)
$users = User::with('businessProfile')->get();
foreach ($users as $user) {
    echo $user->businessProfile->business_name;
}
```

## ❌ Missing Type Declarations

**DON'T** skip strict types:

```php
// ❌ BAD
<?php

namespace App\Services;

class MyService {
    public function calculate($value) {
        return $value * 2;
    }
}
```

**DO** use strict types everywhere:

```php
// ✅ GOOD
<?php

declare(strict_types=1);

namespace App\Services;

class MyService
{
    public function calculate(int $value): int
    {
        return $value * 2;
    }
}
```

## ❌ Exposing Sensitive Data in API Responses

**DON'T** return sensitive model attributes directly:

```php
// ❌ BAD
return response()->json([
    'user' => $user->toArray(), // Can leak token/secret fields if not hidden
]);
```

**DO** keep sensitive attributes hidden and return an explicit payload:

```php
// ✅ GOOD
return response()->json([
    'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ],
]);
```

## ❌ Missing Validation

**DON'T** skip request validation:

```php
// ❌ BAD
public function store(Request $request): JsonResponse
{
    $user = User::create($request->all()); // No validation!
    return response()->json(['user' => $user], 201);
}
```

**DO** use FormRequest or validate:

```php
// ✅ GOOD
public function store(StoreUserRequest $request): JsonResponse
{
    $user = User::create($request->validated());
    return response()->json(['user' => $user], 201);
}
```
