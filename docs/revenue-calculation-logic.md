# Revenue Calculation Logic

## Architecture Overview

The revenue system is split into **two independent GraphQL queries**:

1. **`revenueMetrics`**: Business KPIs with strict period boundaries
2. **`revenueChart`**: Visualization data with intelligent time window expansion

This separation allows metrics to remain accurate to the exact period requested while charts can display more useful visualizations (e.g., MTD on day 2 shows 30 days of chart data but metrics for only 2 days).

**Revenue Metrics Structure**:
- `current`: Paid revenue within the requested period (with trends/comparisons)
- `pipeline`: Active jobs total (pipeline snapshot)
- `in_flight`: Unpaid invoices + unbilled active jobs (money coming soon)

## Conversion Basics
All revenue is calculated in **cents** (integer) to avoid floating point errors.
We use a **base currency** (e.g., USD) for all aggregation.

### Cross-Rate Formula
Exchange rates are stored relative to a common pivot (usually USD). Even if the pivot changes, the logic remains:
`TargetAmount = SourceAmount * (TargetRate / SourceRate)`

Example: Convert 100 EUR to CAD (Base: USD)
- EUR Rate (rel to USD): 0.92
- CAD Rate (rel to USD): 1.35
- `100 * (1.35 / 0.92) = 146.74 CAD`

### Precision Levels
The system tracks the quality of the currency conversion:
-   **EXACT**: Exact exchange rates were found for the specific transaction dates (and the date is not today).
-   **ESTIMATED**: At least one conversion used a fallback rate or the conversion used today's rates (not finalized).

## Paid Revenue (Current)
**Source**: `invoices` table.
-   **Filter**: `status = 'paid'` AND `paid_at` within period.
-   **Date Used**: `paid_at`.

## Pipeline (Active Jobs)
**Source**: `jobs` table.
-   **Filter**: `status != 'cancelled'`. (Includes In Progress, Completed but not paid, etc).
-   **Date Used**: `Now()` (Pipeline is a snapshot of current active work's value).
-   **Note**: Pipeline is standalone and not additive with paid revenue.
-   **Rate Logic**:
    -   `flat`, `buyout`, `per_line`: Returns `contracted_rate`.
    -   `per_word`: Returns `contracted_rate * word_count`.
    -   `hourly`: Returns `contracted_rate * (estimated_hours ?? actual_hours ?? 0)`.
    -   `per_finished_hour`: Returns `contracted_rate * (word_count / 9000)`. (Assumes 9000 words approx 1 Finished Hour).

## In-Flight Revenue (Unpaid + Unbilled)
**Definition**: Money still expected to be collected soon.
-   **Unpaid invoices**: `status in (draft, sent, overdue)` using `issued_at` for conversion date.
-   **Unbilled active jobs**: Active jobs with **no invoices** yet, valued the same way as pipeline.
-   **Purpose**: Feel-good "money in flight" metric for UI; always non-negative.

## Revenue by Source
The `revenueBySource` query groups revenue by where the work came from for revenue source charts.

**Paid Revenue**:
- Uses the same paid invoice filters as `revenueMetrics.current`
- Period-bound to the requested range

**In-Flight Revenue**:
- Uses the same logic as `revenueMetrics.in_flight`
- Includes unpaid invoices + unbilled active jobs
- Snapshot-only (not period-bound)

**Source Attribution Precedence**:
1. Audition source (platform or contact)
2. Job agent
3. Job client
4. Invoice client
5. Unknown

**Source Types**:
- `platform`: Pay-to-play platforms (Voices.com, Voice123, etc.)
- `agent`: Contacts whose `contactable_type` is Agent
- `direct`: Contacts whose `contactable_type` is Client
- `unknown`: Fallback when no source can be resolved
- `other`: Aggregated remainder when more than 10 sources exist

**Grouping Rules**:
- Results are sorted by total (paid + in-flight) descending
- Zero-value sources are filtered out
- Results are limited to `take` entries (default 10)

**Percentages**:
- `percentage_of_total` is calculated from `(paid + in_flight) / grand_total`
- Rounded to one decimal place

## Revenue by Category
The `revenueByCategory` query groups revenue by `ProjectCategory`.

**Paid Revenue**:
- Uses the same paid invoice filters as `revenueMetrics.current`
- Period-bound to the requested range

**In-Flight Revenue**:
- Uses the same logic as `revenueMetrics.in_flight`
- Includes unpaid invoices + unbilled active jobs
- Snapshot-only (not period-bound)

**Category Attribution**:
- Uses `job.category` when available
- Invoices without a job fall back to `UNKNOWN`

**Grouping Rules**:
- Results are sorted by total (paid + in-flight) descending
- Zero-value categories are filtered out
- Results are limited to `take` entries (default 10)

## Trend Analysis
**Formula**: `((Current - Previous) / Previous) * 100`
-   **Previous**: The same duration immediately preceding the current period (exposed as `comparison_total`).
-   Example: If period is 'MTD' (Jan 1 - Jan 15), previous is Dec 17 - Dec 31 (15 days).

## Chart Time Window Expansion

For x-TD filters (MTD/QTD/YTD), the chart query automatically expands the time window when there aren't enough datapoints for useful visualization:

- **MTD**: If ≤7 days into month → expand to 30 days
- **QTD**: If ≤14 days into quarter → expand to 90 days
- **YTD**: If ≤30 days into year → expand to 365 days

**Note**: Metrics queries NEVER expand - they always use strict period boundaries.

## Chart Granularity & Grouping

Chart data is grouped based on the **requested period** (not the effective window size):

-   **1W, 4W, MTD**: Grouped by **Day**
-   **QTD**: Grouped by **Week** (aligned to Monday)
-   **YTD, 1Y, ALL**: Grouped by **Month**

### Chart Response Format

Chart points use **UTC timestamps in milliseconds** for the `timestamp` field and a generic `value` field:

```graphql
type ChartPoint {
  timestamp: Timestamp!  # Unix milliseconds (e.g., 1704067200000)
  value: Int!           # Value in cents (revenue, expenses, etc.)
}
```

The `ChartPoint` type is generic and reusable across all time-series charts (revenue, expenses, profit, etc.).

**Revenue Chart Response**:
```graphql
type RevenueChartResponse implements ChartResponse {
  baseCurrency: String!
  range: ChartRange!          # Either CompactRange enum or DateRange
  effectiveWindow: DateRangeWindow!
  chart: [ChartPoint!]!
}
```

The response includes:
- `baseCurrency`: The currency used for all chart values
- `range`: The requested time range (either a compact range like "MTD" or explicit dates)
- `effectiveWindow`: The actual time window used (may be expanded for visualization)
- `chart`: Array of chart points with UTC timestamps and values in cents

This ensures timezone-safe, unambiguous date handling across the stack.
