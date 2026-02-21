# Database Layer

## Overview

The backend uses **Eloquent ORM**. All models use ULIDs for client-generated IDs and soft deletes.

## Model Pattern

**Location**: `app/Models/User.php`

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasOneDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUlids, Notifiable, SoftDeletes, HasRelationships;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'google_id',
        'google_token',
        'google_refresh_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
        'google_refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'google_token' => 'encrypted',
            'google_refresh_token' => 'encrypted',
        ];
    }


    /**
     * Get the user that owns this model.
     * Special case: User owns itself.
     */
    public function user(): BelongsTo|HasManyDeep|HasOneDeep
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    /**
     * Get the settings associated with the user.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(Settings::class);
    }
}
```

## Key Model Requirements

**Traits for Models:**
```php
use HasUlids;           // ULID primary keys (client-generated)
use SoftDeletes;        // Soft delete support
```

**Traits for Search-Indexed Models:**
```php
use App\Models\Concerns\SearchableDocument;
```

`SearchableDocument` centralizes Scout integration + document shape (`entity_type`, `entity_terms`,
`searchable_text`, timestamps, scout key format), so each searchable model only needs to
define its entity type and indexed field map.

## Auto-Generated Fields

- Prefer explicit inputs. Avoid hidden derived fields unless clearly documented.

## Model Versioning (Laravel Rewind)

Use Laravel Rewind on models that need historical audit trails.

**Requirements:**
```php
use AvocetShores\LaravelRewind\Traits\Rewindable;

class Audition extends Model
{
    use Rewindable;
}
```

Each rewindable table must include a nullable `current_version` column. Version history is stored in `rewind_versions`
using ULID `model_id` values to match the core tables.
User attribution is disabled in `config/rewind.php` because the package assumes integer user IDs.

## Casts and Encryption

**Standard Casts:**
```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',  // Carbon instance
        'status' => JobStatus::class,       // Enum cast
        'is_active' => 'boolean',           // Boolean cast
        'amount' => 'integer',              // Integer cast
    ];
}
```

**Encryption Cast:**
```php
protected function casts(): array
{
    return [
        'google_token' => 'encrypted',         // Encrypt at rest
        'google_refresh_token' => 'encrypted', // Encrypt at rest
        'apple_token' => 'encrypted',          // Encrypt at rest
        'apple_refresh_token' => 'encrypted',  // Encrypt at rest
    ];
}
```

## Monetary Columns

Use the Blueprint macros to create consistent money columns:

```php
$table->money('contracted_rate');       // Non-null, defaults currency to USD
$table->moneyNullable('tax_amount');    // Nullable cents + currency, no defaults
```

Each macro creates `{field}_cents` (bigInteger) and `{field}_currency` (string, 3).

## Monetary Inputs

For GraphQL MoneyInput payloads, use `Money` on the model field and make the
virtual field mass-assignable:

```php
use App\Casts\Money;

protected $fillable = [
    'amount',
    'amount_cents',
    'amount_currency',
];

protected function casts(): array
{
    return [
        'amount' => Money::class,
    ];
}
```

Use `Money::class . ':nullable'` for fields that allow null (e.g. tax amounts).
Do not mass-assign `*_cents` or `*_currency`; treat them as internal storage columns.
MoneyInput requires both `amount_cents` and `currency` when provided.

## Relationships

**Direct Relationship:**
```php
public function settings(): HasOne
{
    return $this->hasOne(Settings::class);
}

public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

**Deep Relationship** (using eloquent-has-many-deep):
```php
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Recording extends Model
{
    use HasRelationships;

    public function user(): HasManyDeep
    {
        // Recording -> Audition -> Agent -> User
        return $this->hasManyDeep(User::class, [Audition::class, Agent::class]);
    }
}
```

**HasManyThrough Relationship:**

Use when accessing related models through an intermediate model. This is Laravel's native way to traverse relationships without needing the eloquent-has-many-deep package.

```php
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Agent extends Model
{
    /**
     * Get all jobs where this agent's contact was used as the agent.
     * Traverses: Agent → Contact → Jobs (via agent_id)
     */
    public function jobs(): HasManyThrough
    {
        return $this->hasManyThrough(
            Job::class,           // Final model
            Contact::class,       // Intermediate model
            'contactable_id',     // Foreign key on intermediate (contacts.contactable_id → agents.id)
            'agent_id',           // Foreign key on final model (jobs.agent_id → contacts.id)
            'id',                 // Local key on this model (agents.id)
            'id'                  // Local key on intermediate (contacts.id)
        )
            ->where('contacts.contactable_type', Agent::class)
            ->where('jobs.user_id', Auth::id())  // User scoping for security
            ->orderBy('jobs.created_at', 'desc'); // Default ordering
    }
}
```

**GraphQL Schema with Pagination:**
```graphql
type Agent {
    id: ULID!
    agency_name: String

    "Jobs associated with this agent (paginated, filterable, sortable)"
    jobs(
        "Filter jobs by specific conditions"
        where: _ @whereConditions(columns: [
            "status",
            "project_title",
            "created_at"
        ])

        "Sort jobs by specific columns"
        orderBy: _ @orderBy(columns: [
            "created_at",
            "session_date"
        ])

        "Number of items per page"
        first: Int = 20

        "Page number"
        page: Int
    ): [Job!]! @hasManyThrough(type: PAGINATOR, defaultCount: 20)
}
```

**GraphQL Query Examples:**
```graphql
# Get 5 most recent jobs
agent(id: "01JQXYZ...") {
  jobs(first: 5, orderBy: [{ column: CREATED_AT, order: DESC }]) {
    data {
      project_title
      created_at
    }
  }
}

# Filter by status and sort
agent(id: "01JQXYZ...") {
  jobs(
    where: { column: STATUS, operator: EQ, value: "completed" }
    orderBy: [{ column: SESSION_DATE, order: ASC }]
  ) {
    data {
      project_title
      session_date
    }
  }
}
```

**Key Points:**
- Lighthouse's `@hasManyThrough` directive automatically recognizes the relationship
- No custom resolvers needed
- `@whereConditions` enables filtering on specified columns
- `@orderBy` enables sorting by specified columns
- User scoping in the relationship ensures data isolation
- Default ordering can be set in the relationship definition

## Eager Loading (N+1 Prevention)

✅ **DO**: Eager load relationships to prevent N+1 queries
```php
$users = User::with('settings')->get(); // Single query for users + settings

// Multiple relationships
$users = User::with(['settings', 'agents.auditions'])->get();
```

❌ **DON'T**: Load relationships in loop
```php
$users = User::all();
foreach ($users as $user) {
    $settings = $user->settings; // N+1 query problem
}
```

## Query Scopes

Query scopes encapsulate reusable query logic for cleaner, more maintainable code.

**Local Scopes** (prefix with `scope`):
```php
class Invoice extends Model
{
    /**
     * Scope to filter paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', InvoiceStatus::PAID);
    }

    /**
     * Scope to filter by authenticated user
     */
    public function scopeForUser($query)
    {
        return $query->where('user_id', Auth::id());
    }
}
```

**Usage**:
```php
// Single scope
$invoices = Invoice::paid()->get();

// Chained scopes
$invoices = Invoice::forUser()->paid()->get();

// With pagination
$invoices = Invoice::forUser()->paid()->paginate(20);
```

**ExchangeRate Scopes Example**:
```php
class ExchangeRate extends Model
{
    public function scopeForCurrency($query, string $code)
    {
        return $query->where('currency_code', $code);
    }

    public function scopeForCurrencies($query, array $codes)
    {
        return $query->whereIn('currency_code', $codes);
    }

    public function scopeOnOrBefore($query, Carbon $date)
    {
        return $query->where('effective_date', '<=', $date->format('Y-m-d'))
            ->orderBy('effective_date', 'desc');
    }

    public function scopeInDateRange($query, Carbon $start, Carbon $end, int $bufferDays = 45)
    {
        return $query->whereBetween('effective_date', [
            $start->copy()->subDays($bufferDays),
            $end
        ]);
    }
}
```

**Usage**:
```php
// Get EUR rate for a specific date
$rate = ExchangeRate::forCurrency('EUR')
    ->onOrBefore($date)
    ->first();

// Get multiple currencies for a date range
$rates = ExchangeRate::forCurrencies(['EUR', 'CAD', 'GBP'])
    ->inDateRange($startDate, $endDate)
    ->get();
```

**Benefits**:
- Encapsulates business logic in the model
- Makes queries self-documenting
- Reusable across the application
- Composable (can chain multiple scopes)

## Computed Attributes

Computed attributes define virtual properties that are calculated dynamically.

**Pattern**:
```php
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Model
{
    /**
     * Get the user's preferred base currency
     */
    protected function baseCurrency(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->relationLoaded('settings')) {
                    $this->load('settings');
                }
                return $this->settings?->currency ?? 'USD';
            }
        );
    }
}
```

**Usage**:
```php
$currency = $user->base_currency; // Accesses computed attribute
```

**Benefits**:
- Centralizes derived logic
- Clean attribute access syntax
- Automatically handles relationship loading
- Reusable across services and queries

**Use Cases**:
- Computed financial values (totals, conversions)
- Derived status fields
- Configuration resolution with fallbacks
- Relationship-based calculations
