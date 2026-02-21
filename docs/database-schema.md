# Database Schema

> **Purpose:** This document defines the database schema for the Voice Actor CRM. It contains table definitions, relationships, field specifications, and notes explaining schema design choices.
>
> **Scope:** Schema structure only. For design rationale and architectural decisions, see `database-schema-decisions.md` . Implementation details, packages, and code patterns belong elsewhere.

## Overview

* **IDs**: ULIDs on domain tables, `bigint` on system/version tables
* **Soft deletes**: All domain tables (version history is append-only)
* **Timestamps**: `created_at`, `updated_at` everywhere; `deleted_at` on soft-deletable tables

---

## Tables

### users

```
id (ulid)
email (string)
name (string)
email_verified_at (datetime, nullable)
remember_token (string, nullable)
google_id (string, nullable)
google_token (string, nullable)
google_refresh_token (string, nullable)
apple_id (string, nullable)
apple_token (string, nullable)
apple_refresh_token (string, nullable)
created_at
updated_at
deleted_at
```

**Relationships:**
* hasOne Settings
* hasOne BusinessProfile
* hasMany Contacts
* hasMany Platforms
* hasMany Auditions
* hasMany Jobs
* hasMany Invoices
* hasMany ExpenseDefinitions
* hasMany Expenses
* hasMany Attachments
* hasMany Activities
* hasMany Notes

**Notes:**
* No password column - authentication via email codes or OAuth (Google/Apple)

---

### auth_codes

```
id (ulid)
user_id (fk)
purpose (string - auth)
code_hash (string)
expires_at (datetime)
used_at (datetime, nullable)
attempts (unsigned tiny int, default: 0)
created_at
updated_at
deleted_at
```

**Relationships:**
* belongsTo User

**Notes:**
* 6-digit email codes
* 10-minute TTL, one-time use
* Invalidated after 5 failed attempts

---

### settings

```
id (ulid)
user_id (fk)
timezone (string)
currency (string)
language (string)
activity_audition_response_due_hours (unsigned small int, default: 48)
activity_job_session_upcoming_hours (unsigned small int, default: 24)
activity_job_delivery_due_hours (unsigned small int, default: 24)
activity_invoice_due_soon_days (unsigned small int, default: 7)
activity_usage_rights_expiring_days (unsigned small int, default: 30)
created_at
updated_at
deleted_at
```

**Relationships:**
* belongsTo User

**Notes:**
* Activity threshold fields control when date-based activities become active.
* These fields are internal-only for now and are not exposed in GraphQL.

---

### business_profile

```
id (ulid)
user_id (fk)
business_name (string, nullable)
address_street (string, nullable)
address_city (string, nullable)
address_state (string, nullable)
address_country (string, nullable)
address_postal (string, nullable)
phone (string, nullable)
email (string, nullable)
payment_instructions (text, nullable)
logo_path (string, nullable)
created_at
updated_at
deleted_at
```

**Relationships:**
* belongsTo User

**Notes:**
* Used by Invoice PDF generation for seller block

---

### contacts

```
id (ulid)
user_id (fk)
contactable_type (string)
contactable_id (ulid)
name (string)
email (string, nullable)
phone (string, nullable)
phone_ext (string, nullable)
address_street (string, nullable)
address_city (string, nullable)
address_state (string, nullable)
address_country (string, nullable)
address_postal (string, nullable)
last_contacted_at (datetime, nullable)
created_at
updated_at
deleted_at
```

**Relationships:**
* belongsTo User
* morphTo contactable (Agent, Client)
* morphMany Note

**Polymorphic:** `contactable_type` / `contactable_id` points to Agent or Client tables.

---

### agents

```
id (ulid)
agency_name (string, nullable)
commission_rate (int - basis points, 1000 = 10%)
territories (json - array of ISO 3166-1 alpha-2 country codes)
is_exclusive (bool)
contract_start (date, nullable)
contract_end (date, nullable)
created_at
updated_at
deleted_at
```

**Relationships:**
* morphOne Contact (contactable)

---

### clients

```
id (ulid)
type (enum: individual, company)
industry (string, nullable)
payment_terms (string, nullable)
created_at
updated_at
deleted_at
```

**Relationships:**
* morphOne Contact (contactable)

---

### platforms

```
id (ulid)
user_id (fk)
name (string)
url (string, nullable)
username (string, nullable)
external_id (string, nullable)
created_at
updated_at
deleted_at
```

**Relationships:**
* belongsTo User
* morphMany Note

**Notes:**
* Pay-to-play marketplaces (Voices.com, Voice123, Bodalgo, etc.)
* Not a contact - user has an account with them, not a relationship
* Used as audition source via polymorphic relationship

---

### auditions

```
id (ulid)
user_id (fk)
sourceable_type (string - 'platform' or 'contact')
sourceable_id (ulid)
source_reference (string, nullable - external ID from platform)
project_title (string)
brand_name (string, nullable)
character_name (string, nullable)
category (enum: commercial, animation, video_game, audiobook, elearning, corporate, documentary, narration, promo, trailer, radio_imaging, ivr, explainer, podcast, dubbing, announcement, meditation, tv_series, film, coaching, other)
word_count (int, nullable)
budget_min (int, nullable - cents)
budget_max (int, nullable - cents)
quoted_rate (int, nullable - cents)
rate_type (enum: flat, hourly, per_finished_hour, per_word, per_line, buyout)
response_deadline (datetime, nullable)
project_deadline (datetime, nullable)
status (enum: received, preparing, submitted, shortlisted, callback, won, lost, expired)
current_version (bigint, nullable)
created_at
updated_at
deleted_at
```

**Relationships:**
* belongsTo User
* morphTo sourceable (Platform or Contact)
* morphMany UsageRights
* morphMany Attachment
* morphMany Note
* hasOne Job (via job.audition_id)

**Notes:**
* `expired` status determined by `response_deadline` passing; can be set via scheduled job or query scope

**Polymorphic:** `sourceable_type` / `sourceable_id` points to Platform or Contact tables.

---

### jobs

```
id (ulid)
user_id (fk)
audition_id (fk, nullable - null for direct bookings)
client_id (fk - Contact)
agent_id (fk, nullable - Contact; app logic validates it's an agent type)
project_title (string)
brand_name (string, nullable - for exclusivity tracking)
character_name (string, nullable)
category (enum: commercial, animation, video_game, audiobook, elearning, corporate, documentary, narration, promo, trailer, radio_imaging, ivr, explainer, podcast, dubbing, announcement, meditation, tv_series, film, coaching, other)
word_count (int, nullable)
contracted_rate (int - cents)
currency (string - ISO 4217)
rate_type (enum: flat, hourly, per_finished_hour, per_word, per_line, buyout)
estimated_hours (decimal, nullable)
actual_hours (decimal, nullable)
session_date (datetime, nullable - single session for now, can expand later)
delivery_deadline (datetime, nullable)
delivered_at (datetime, nullable)
archived_at (datetime, nullable)
status (enum: booked, in_progress, delivered, revision, completed, cancelled)
current_version (bigint, nullable)
created_at
updated_at
deleted_at
```

**Relationships:**
* belongsTo User
* belongsTo Audition (nullable)
* belongsTo Contact as client
* belongsTo Contact as agent (nullable)
* morphMany UsageRights
* morphMany Attachment
* morphMany Note
* hasMany Invoice

---

### usage_rights

```
id (ulid)
usable_type (string - 'audition' or 'job')
usable_id (ulid)
type (enum: broadcast, non_broadcast)
media_types (json - array of: tv, radio, digital, social_media, streaming, cinema, print, outdoor, internal, podcast, video_game, all_media)
geographic_scope (enum: local, regional, national, multi_national, worldwide)
duration_type (enum: fixed, perpetual)
duration_months (int, nullable - only for fixed)
start_date (date, nullable)
expiration_date (date, nullable)
exclusivity (bool, default false)
exclusivity_category (string, nullable - freeform, autocomplete from existing jobs)
ai_rights_granted (bool, default false)
renewal_reminder_sent (bool, default false)
current_version (bigint, nullable)
created_at
updated_at
deleted_at
```

**Relationships:**
* morphTo usable (Audition or Job)
* morphMany Note

**Notes:**
* When job is created from audition, copy usage_rights records to job

**Polymorphic:** `usable_type` / `usable_id` points to Audition or Job tables.

---

### invoices

```
id (ulid)
user_id (fk)
job_id (fk, nullable)
client_id (fk - Contact)
invoice_number (string)
issued_at (date)
due_at (date)
subtotal (int - cents)
tax_rate (decimal, nullable)
tax_amount (int - cents, nullable)
total (int - cents)
currency (string - ISO 4217)
status (enum: draft, sent, paid, overdue, cancelled)
paid_at (date, nullable)
current_version (bigint, nullable)
created_at
updated_at
deleted_at
```

**Relationships:**
* belongsTo User
* belongsTo Job (nullable)
* belongsTo Contact as client
* hasMany InvoiceItem
* morphMany Attachment
* morphMany Note

**Notes:**
* PDF generated on-demand via `laraveldaily/laravel-invoices`
* Seller info pulled from business_profile

---

### invoice_items

```
id (ulid)
invoice_id (fk)
description (string)
quantity (decimal)
unit_price (int - cents)
amount (int - cents)
created_at
updated_at
deleted_at
```

**Relationships:**
* belongsTo Invoice

---

### rewind_versions

```
id (bigint, auto increment)
model_type (string)
model_id (ulid)
old_values (text, nullable)
new_values (text, nullable)
version (bigint)
is_snapshot (bool)
user_id (ulid, nullable)
created_at
updated_at
```

**Relationships:**
* morphTo model (Audition, Job, Invoice, UsageRight, etc.)
* belongsTo User (optional, when user tracking is enabled)

**Notes:**
* Version history for Rewindable models; `current_version` points to the active version.

---

### expense_definitions

```
id (ulid)
user_id (fk)
name (string)
amount (int - cents)
currency (string - ISO 4217)
category (enum: equipment, software, studio, training, marketing, membership, travel, office, professional_services, other)
recurrence (enum: one_off, weekly, monthly, yearly)
recurrence_day (int, nullable - day of month/week)
starts_at (date)
ends_at (date, nullable - null = indefinite)
is_active (bool, default true)
created_at
updated_at
deleted_at
```

**Relationships:**
* belongsTo User
* hasMany Expense

---

### expenses

```
id (ulid)
user_id (fk)
expense_definition_id (fk, nullable - null for manual one-off entries)
description (string)
amount (int - cents)
currency (string - ISO 4217)
category (enum: equipment, software, studio, training, marketing, membership, travel, office, professional_services, other)
date (date)
created_at
updated_at
deleted_at
```

**Relationships:**
* belongsTo User
* belongsTo ExpenseDefinition (nullable)
* morphMany Attachment
* morphMany Note

---

### attachments

```
id (ulid)
user_id (fk)
attachable_type (string)
attachable_id (ulid)
filename (string)
original_filename (string)
mime_type (string)
size (int - bytes)
disk (string)
path (string)
category (enum: script, recording, contract, deliverable, receipt, invoice_pdf, headshot, agreement, other)
metadata (json, nullable)
created_at
updated_at
deleted_at
```

**Relationships:**
* belongsTo User
* morphTo attachable (Audition, Job, Expense, Invoice, Contact)

**GQL Notes:**
* `metadata` represented as JSON scalar
* Shape varies by file type (audio: duration, sample_rate; image: width, height; etc.)

**Polymorphic:** `attachable_type` / `attachable_id` points to Audition, Job, Expense, Invoice, or Contact tables.

---

### activities

```
id (ulid)
user_id (fk)
targetable_type (string)
targetable_id (ulid)
targetable_version (int, nullable)
trigger (enum: audition_response_due, job_session_upcoming, job_delivery_due, job_revision_requested, invoice_due_soon, invoice_overdue, usage_rights_expiring)
action (enum: snoozed, archived)
snoozed_until (datetime, nullable)
created_at
updated_at
deleted_at
```

**Relationships:**
* belongsTo User
* morphTo targetable (Audition, Job, Invoice, UsageRights)
* morphMany Note

**Notes:**
* Activity items are generated by the activity service based on dates/status and persisted here
* `targetable_version` stores the Rewind version number captured at first trigger; later updates do not mutate this snapshot version
* `trigger` identifies which condition surfaced the item (an entity can have multiple attention-worthy conditions)
* `snoozed_until` only populated when action is `snoozed`
* When a trigger condition becomes false, active/snoozed rows are archived (`action = archived`) instead of being deleted
* Archived actions are terminal for that trigger + target and are ignored by subsequent activity generation runs
* When rendering the activity, prefer the stored versioned snapshot; if unavailable, fallback to the live targetable

**Targetable types by trigger:**

| Trigger | Target Model | Source Field |
|---------|--------------|--------------|
| `audition_response_due` | Audition | `response_deadline` |
| `job_session_upcoming` | Job | `session_date` |
| `job_delivery_due` | Job | `delivery_deadline` |
| `job_revision_requested` | Job | `status = revision` |
| `invoice_due_soon` | Invoice | `due_at` |
| `invoice_overdue` | Invoice | `due_at` + `status != paid` |
| `usage_rights_expiring` | UsageRights | `expiration_date` |

**Polymorphic:** `targetable_type` / `targetable_id` points to Audition, Job, Invoice, or UsageRights tables.

---

### notes

```
id (ulid)
user_id (fk)
notable_type (string)
notable_id (ulid)
content (text)
created_at
updated_at
deleted_at
```

**Relationships:**
* belongsTo User
* morphTo notable (Audition, Job, Contact, Invoice, Expense, UsageRights, Activity, Platform)

**Notes:**
* Unified notes system replacing inline `notes` fields on individual tables
* Allows multiple timestamped notes per entity
* Supports tracking conversation history or updates over time

**Polymorphic:** `notable_type` / `notable_id` points to Audition, Job, Contact, Invoice, Expense, UsageRights, Activity, or Platform tables.

---

### exchange_rates

id (ulid)
currency_code (string - ISO 4217)
rate (decimal - 24,12)
base_currency (string - default 'USD')
effective_date (date)
created_at
updated_at


**Relationships:**

* used by Invoices (logic-based join on currency and paid_at)
* used by Jobs (logic-based join on currency and delivery_deadline)

**Notes:**

* Stores a daily snapshot of exchange rates relative to the base currency.
* Unique constraint recommended on [currency_code, effective_date] to ensure data integrity.
* Decimal precision (24,12) prevents rounding errors in financial reports and supports crypto assets.
