# Development Workflow

## Setup Commands

**Initial Setup:**
```bash
composer run setup
# Runs: install, .env copy, key generation, migrations, npm install, npm build
```

**Development:**
```bash
composer run dev
# Runs concurrently: server, queue, logs, vite
```

**Individual Services:**
```bash
php artisan serve              # Start dev server (port 8000)
php artisan queue:listen       # Process queue jobs
php artisan pail               # Tail logs in real-time
npm run dev                    # Vite dev server
```

## Testing

**Run All Tests:**
```bash
composer test
# or
php artisan test
```

Note:
- PHPUnit is configured with `memory_limit=512M` in `phpunit.xml` to reduce OOM failures in larger GraphQL suites.

**Run Specific Test:**
```bash
php artisan test tests/Feature/GoogleAuthControllerTest.php
```

**Run With Coverage:**
```bash
php artisan test --coverage
```

**Pest-Specific Commands:**
```bash
vendor/bin/pest                    # Run all tests
vendor/bin/pest --filter=Activity  # Run tests matching "Activity"
vendor/bin/pest --parallel         # Run tests in parallel
```

## Code Quality

**Format Code:**
```bash
./vendor/bin/pint                  # Fix all files
./vendor/bin/pint --test           # Check without fixing
./vendor/bin/pint app/Models/      # Fix specific directory
```

**Clear Caches:**
```bash
php artisan config:clear           # Clear config cache
php artisan cache:clear            # Clear application cache
php artisan route:clear            # Clear route cache
php artisan view:clear             # Clear view cache
```

## Database

**Migrations:**
```bash
php artisan migrate                # Run migrations
php artisan migrate:fresh          # Drop all tables and re-migrate
php artisan migrate:fresh --seed   # Also run seeders
php artisan migrate:rollback       # Rollback last migration
```

**Seeders:**
```bash
php artisan db:seed                # Run all seeders
php artisan db:seed --class=ExchangeRateSeeder  # Run specific seeder
php artisan db:seed --class=DevelopmentSeeder  # Full dev dataset (jobs, invoices, top-10 revenue-by-source samples, activities, etc.)
```

Notes:
- DevelopmentSeeder includes a couple of jobs and auditions with multiple usage rights for list-field testing.
- DevelopmentSeeder generates activities via `ActivityService` (condition-driven) and then applies a small set of demo action states (snoozed/archived) on valid triggers.

## Scheduled Tasks

**Sync Exchange Rates:**
```bash
php artisan app:sync-exchange-rates
# Runs daily via scheduler
```

## Search (Scout + Typesense)

**Start Typesense (Docker):**
```bash
docker compose -f docker-compose.typesense.yml up -d
```

**Stop Typesense:**
```bash
docker compose -f docker-compose.typesense.yml down
```

**Reindex all search collections:**
```bash
php artisan search:reindex --flush
```

Notes:
- Ensure `.env` sets `SCOUT_DRIVER=typesense`.
- Default local Typesense endpoint is `http://localhost:8108`.
- `search:reindex` imports contacts, clients, agents, jobs, invoices, auditions, expenses, platforms, and notes.
- Run `php artisan search:reindex --flush` whenever search document fields or Typesense schema settings change.

### Adding a searchable model

When a new entity should participate in global search:

1. Add the trait on the model:
   - `App\Models\Concerns\SearchableDocument` (composes Scout + shared search document shape)
2. Implement required methods on the model:
   - `searchEntityType(): string` (lowercase index type, e.g. `campaign`)
   - `searchDocumentFields(): array` (field/value pairs to index)
   - For relation-derived fields, prefer nested arrays (for example `client => ['name' => ...]`, `agent => ['name' => ...]`). The shared search trait flattens those to `relation__field` for indexing and search responses normalize back to GraphQL-style paths (`client.name`, `agent.name`).
3. If indexed fields come from relations, implement `searchRelationsForIndex(): array` so those relations are eager loaded before indexing.
4. If ownership comes from a relation (not `user_id` on the model), override `searchUserIdForIndex(): string` and keep `shouldBeSearchable()` guard logic.
5. Register the model in `app/Search/SearchableEntities.php`:
   - `map()`
   - `modelByEntityType()`
6. If you add a new text field key, include it in `optionalTextFields()` inside `app/Search/SearchableEntities.php` so Typesense schema + query/highlight config stay in sync.
7. Update GraphQL search schema (`graphql/search.graphql`) if the new entity should be selectable by `types` and resolvable in `SearchEntityResult`.
8. Reindex:
   - `php artisan search:reindex --flush`

## GraphQL Tools (Local Only)

**GraphiQL:**
```bash
http://localhost:8000/graphiql
```
Enabled only when `APP_ENV=local` (or when `GRAPHIQL_ENABLED=true`).

**Schema Graph (Voyager):**
```bash
http://localhost:8000/graphql-voyager
```
Available only in local environment and points at `/graphql`. Assets are self-hosted under `public/vendor/graphql-voyager`.

**Updating Voyager Assets:**
```bash
repo_root="$(pwd)"
tmpdir="$(mktemp -d)"
cd "$tmpdir"
curl -L -o react.tgz https://registry.npmjs.org/react/-/react-18.3.1.tgz
curl -L -o react-dom.tgz https://registry.npmjs.org/react-dom/-/react-dom-18.3.1.tgz
curl -L -o graphql-voyager.tgz https://registry.npmjs.org/graphql-voyager/-/graphql-voyager-2.1.0.tgz
mkdir -p react react-dom graphql-voyager
tar -xzf react.tgz -C react --strip-components=1
tar -xzf react-dom.tgz -C react-dom --strip-components=1
tar -xzf graphql-voyager.tgz -C graphql-voyager --strip-components=1
mkdir -p "$repo_root/public/vendor/react" "$repo_root/public/vendor/react-dom" "$repo_root/public/vendor/graphql-voyager"
cp react/umd/react.production.min.js "$repo_root/public/vendor/react/react.production.min.js"
cp react-dom/umd/react-dom.production.min.js "$repo_root/public/vendor/react-dom/react-dom.production.min.js"
cp graphql-voyager/dist/voyager.css "$repo_root/public/vendor/graphql-voyager/voyager.css"
cp graphql-voyager/dist/voyager.standalone.js "$repo_root/public/vendor/graphql-voyager/voyager.standalone.js"
```

## Debugging

**Tinker (REPL):**
```bash
php artisan tinker

# Inside tinker:
>>> User::count()
>>> $user = User::first()
>>> $user->settings
>>> DB::table('users')->where('email', 'test@example.com')->first()
```

**Pail (Log Tailing):**
```bash
php artisan pail                   # Tail logs with pretty formatting
php artisan pail --filter=error    # Only show errors
```

**Query Logging:**
```php
use Illuminate\Support\Facades\DB;

DB::enableQueryLog();
// ... run queries
dd(DB::getQueryLog());
```
