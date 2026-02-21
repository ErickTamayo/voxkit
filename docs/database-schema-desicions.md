# Schema Design Decisions

This document captures the reasoning behind key decisions made during database schema design.

---

## Table of Contents

01. [Foundational Decisions](#foundational-decisions)
02. [Authentication](#authentication)
03. [Contact System Architecture](#contact-system-architecture)
04. [Platforms](#platforms)
05. [Auditions and Jobs](#auditions-and-jobs)
06. [Usage Rights](#usage-rights)
07. [Invoicing](#invoicing)
08. [Expenses](#expenses)
09. [Attachments](#attachments)
10. [Exchange Rates](#exchange-rates)
11. [Enums and Data Types](#enums-and-data-types)

---

## Foundational Decisions

### ULIDs for Primary Keys

ULIDs are sortable by time and support distributed record creation without ID collisions. They preserve chronological ordering while remaining globally unique.

### Soft Deletes Everywhere

Voice actors need historical data for tax purposes, analytics, and audit trails. Hard deleting records would break reporting and potentially lose important business history.

### Timestamps on All Tables

Standard `created_at` , `updated_at` , `deleted_at` on every table for consistency and auditability.

---

## Authentication

### No Password Column

Users table has no password field. Authentication via email codes or OAuth (Google/Apple).

**Why:**
* Reduces security surface (no passwords to hash, store, or breach)
* Modern low-friction UX
* Simpler onboarding flow
* Short-lived codes reduce long-term credential risk

### Auth Codes Table

6-digit codes are stored in a dedicated `auth_codes` table with a short TTL, one-time use enforcement, and failed-attempt tracking.

**Why:**
* Keeps auth code data separate from core user records
* Allows strict TTL, one-time usage, and lockout tracking after repeated failures
* Supports both token and session flows without passwords
* Works with layered rate limits (email+IP, email-only, IP-only, resend cooldown) to reduce brute-force and spray risk

### OAuth Fields Directly on Users Table

Store `google_id` , `google_token` , `google_refresh_token` , `apple_id` , `apple_token` , `apple_refresh_token` directly on users table.

**Why:** Only supporting two OAuth providers (Google, Apple). A separate providers table would be overengineering. If more providers are added later, migration is straightforward.

---

## Contact System Architecture

### Polymorphic Contactables

Base `contacts` table with `contactable_type` / `contactable_id` pointing to type-specific tables ( `agents` , `clients` ).

**Why:**
* Subtypes diverge significantly in their specific fields
* Agents have commission_rate, territories, contract dates — very different from clients
* Base `contacts` table stays lean and universally searchable
* Type-specific tables hold only relevant columns
* Adding new contact types doesn't bloat the base table

### Agents with agency_name Field

Agents have an `agency_name` string field rather than a separate agencies table.

**Why:**
* VoiceOverview (market leader) keeps this simple
* Voice actors in the gig economy don't need complex agency hierarchies
* An agent is just "someone who sends me work and takes a cut"
* If user has an agent at WME, they create an agent with `agency_name: "WME"`

### Single Clients Table with Type

Single `clients` table with `type` enum (individual, company) rather than separate direct_clients and production_companies tables.

**Why:** Both have nearly identical fields (industry, payment_terms). The `type` field distinguishes them when needed without redundant tables.

### No representation_type on Agents

After researching the voice acting industry, found that commercial/theatrical/voiceover categorization comes from traditional talent agencies (on-camera work). For voiceover-focused CRMs, work categories are already tracked on auditions/jobs. Agency specialization is implicit in the work they send.

---

## Platforms

### Separate Platforms Table (Not Contacts)

Platforms (Voices.com, Voice123, Bodalgo) are pay-to-play marketplaces stored in their own table.

**Why:**
* You have an *account* with them, not a *relationship*
* You don't contact them about auditions — the platform is automated
* Auditions come via their system with external reference IDs
* Future integration potential (email parsing, provider APIs)

### Audition Source Polymorphism

`auditions.sourceable_type` can be 'platform' or 'contact'.

**Why:**
* Auditions can come from platforms OR from agents/direct clients
* Enables tracking "where do my bookings actually come from"
* Analytics: booking rate by source (agent vs Voices.com vs direct)

---

## Auditions and Jobs

### Separate Entities with Nullable FK

Auditions and Jobs are separate tables with `jobs.audition_id` as nullable FK.

**Why:**
* Direct bookings skip the audition phase entirely (need `audition_id` nullable)
* Data shapes differ: auditions have response_deadline, budget ranges; jobs have contracted_rate, session details
* Cleaner queries: "show me all jobs" vs "show me auditions with callbacks"

### Duplicated Fields Between Audition and Job

Fields like `project_title` , `brand_name` , `category` , `rate_type` exist on both tables.

**Why:**
* When job is created from audition, data is copied over
* Script content is handled as `attachments` records (category `script`) instead of inline text fields
* Job may have different final terms than audition proposed
* Independence allows each record to be complete and self-contained

### Expired Status via response_deadline

No separate `expires_at` column on auditions. `expired` status determined by `response_deadline` passing.

**Why:**
* `response_deadline` already captures when the audition expires
* Expiration handled by scheduled job updating status, or query scope
* Single source of truth for deadline

---

## Usage Rights

### Polymorphic (Audition or Job)

`usage_rights` table with `usable_type` / `usable_id` pointing to audition or job.

**Why:**
* Auditions typically specify expected usage rights upfront (part of evaluating if rate is worth it)
* Jobs have final negotiated usage rights
* When job is created from audition, copy usage_rights records
* Job might negotiate different final terms, so they need independent records

### Exclusivity on Usage Rights

`exclusivity` and `exclusivity_category` live on `usage_rights` , not on client.

**Why:**
* Same client might hire you for Coca-Cola one month (soft drinks exclusivity) and Ford the next (automotive)
* Exclusivity is per-job, not per-relationship
* Conflict check: "Do I have active jobs with unexpired exclusivity in category X?"

### brand_name on Jobs

`jobs.brand_name` for the brand being advertised (separate from client).

**Why:**
* Production company (client) might hire you for multiple brands
* Exclusivity conflicts are about brands, not clients
* Enables searching "all my Coca-Cola work"

### ai_rights_granted Flag

Boolean `ai_rights_granted` on usage_rights, defaulting to false.

**Why:**
* Industry-specific concern: AI voice cloning rights are a major issue
* Many contracts now include AI clauses
* Flag allows tracking and potentially warning users

---

## Invoicing

### Store Data, Generate PDF On-Demand

Store invoice data in our tables, generate PDF via `laraveldaily/laravel-invoices` package.

**Why:**
* Users can generate/download/email PDFs manually
* Full control over data
* No third-party dependencies for core functionality
* Payment integrations (Wave, QuickBooks, Stripe) can be Phase 2

### Separate business_profile Table

Invoice seller info (business name, address, logo) comes from `business_profile` table, not `settings` .

**Why:**
* Separates concerns: settings = preferences, business_profile = legal/invoicing identity
* Business info might differ from personal account info
* Invoice email might differ from login email

### invoice_items Table

Separate `invoice_items` table for line items.

**Why:**
* Invoices can have multiple line items
* One job might invoice for session + usage fees + expenses
* Proper invoice structure for PDF generation

---

## Expenses

### expense_definitions + expenses Pattern

Two tables: `expense_definitions` (template/rule) and `expenses` (realized instance).

**Why:**
* Recurring expenses (subscriptions, rent) need a definition that spawns actual expense records
* Scheduler checks definitions, creates expense records when due
* One-off expenses: create definition with `recurrence: one_off`, spawns single expense
* Clear separation of "what should happen" vs "what happened"
* Historical accuracy: changing a definition doesn't alter past expenses

### Category Enum on Both Tables

`category` enum exists on both `expense_definitions` and `expenses` .

**Why:**
* Expenses can be created without a definition (manual one-off entries)
* Category needed for tax reporting regardless of source
* Denormalized for query simplicity

---

## Attachments

### Single Polymorphic Table

One `attachments` table with `attachable_type` / `attachable_id` .

**Why:**
* Many entities need files: auditions (scripts, recordings), jobs (contracts, deliverables), expenses (receipts), invoices (PDFs), contacts (agreements)
* Same storage logic applies to all
* `category` enum distinguishes file types

### Metadata as JSON

`metadata` column as JSON for type-specific attributes (duration for audio, dimensions for images, etc.)

**Why:**
* Different file types need different attributes
* Separate columns would be sparse (lots of nulls)
* JSON is flexible and extensible

### GraphQL: JSON Scalar

`metadata` represented as JSON scalar in GraphQL.

**Why:**
* Lighthouse has built-in JSON scalar
* Apollo handles it on the client
* Can tighten typing later if needed
* MVP simplicity

---

## Exchange Rates

### Daily JSON Caching Strategy

Exchange rates are fetched from OpenExchangeRates and cached as flat JSON files (`storage/app/exchange-rates/YYYY-MM-DD.json`) before being inserted into the database.

**Why:**
*   **Cost Efficiency**: We only fetch missing days. If we need to rebuild the database, we can re-ingest from JSON files without hitting the API limit. The only exception is that we always re-fetch today and yesterday to capture end-of-day corrections.
*   **Reliability**: External APIs can go down. Having a local file cache ensures we have a backup of the source truth.
*   **Immutability**: Historical exchange rates don't change. Once a day is captured, it's static.

### Base Currency Default (USD)

All rates are stored relative to USD.

**Why:**
*   Standard practice for exchange rate APIs.
*   Simplifies conversion logic (Convert A -> USD -> B).

---

## Enums and Data Types

### Category Enum (Auditions/Jobs)

Comprehensive enum based on industry research:

```
commercial, animation, video_game, audiobook, elearning, corporate, 
documentary, narration, promo, trailer, radio_imaging, ivr, explainer, 
podcast, dubbing, announcement, meditation, tv_series, film, coaching, other
```

**Source:** VoiceOverview categories, Voices.com, industry articles.

**"other" Included:** Users will encounter edge cases. Better to have catch-all than force incorrect categorization.

### Commission Rate as Basis Points

Store `commission_rate` as integer in basis points (1000 = 10%).

**Why:**
* Consistent with "store money as cents" philosophy
* Avoids floating point issues
* Handles fractional percentages (10.5% = 1050)

### Money as Cents

All monetary amounts stored as integers in cents.

**Why:**
* Avoids floating point precision issues
* Standard practice for financial data
* Currency stored separately as ISO 4217 code

### Territories as JSON Array

`agents.territories` as JSON array of ISO 3166-1 alpha-2 country codes.

**Why:**
* Agents can represent you in multiple countries
* Country codes are standardized
* JSON array allows multi-select

---

## External Packages

| Package | Purpose |
|---------|---------|
| `laraveldaily/laravel-invoices` | PDF invoice generation |
| Email auth codes | Passwordless authentication |
| Laravel Socialite | Google/Apple OAuth |
| `propaganistas/laravel-disposable-email` | Disposable email filtering |

---

## Future Considerations

When expanding the schema, consider:

01. **Multiple sessions per job** — May need `job_sessions` table
02. **Contact relationships** — People at companies (casting directors at production companies)
03. **External integrations** — Wave, QuickBooks, Stripe Invoicing
04. **Notification preferences** — More granular settings
05. **Rate cards** — User-defined pricing templates
06. **Conflict checking** — Automated exclusivity violation detection
