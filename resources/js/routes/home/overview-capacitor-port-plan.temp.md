# Temporary Guide: Capacitor Overview Port (RN -> React)

Status: Draft (temporary planning doc for this migration)  
Scope: Port Overview from the source React Native implementation into voxkit for Capacitor only, with parity-first behavior.  
Working order: small components -> screen composition -> tabs integration.

## 1. Source-of-truth inventory (from source app)

### 1.1 Entry and tab shell
- `frontend/src/screens/overview/OverviewScreen/Overview.screen.tsx`
- `frontend/src/screens/overview/OverviewScreen/OverviewTabs.tsx`
- `frontend/src/screens/overview/components/OverviewTabBar.tsx`
- `frontend/src/screens/overview/components/OverviewTabBar/TabBadge.tsx`

### 1.2 Header animation and layout coupling
- `frontend/src/screens/overview/components/AnimatedHeader/AnimatedHeader.tsx`
- `frontend/src/screens/overview/components/AnimatedHeader/useHeaderAnimation.ts`
- `frontend/src/screens/overview/components/AnimatedHeader/useScrollTrigger.ts`
- `frontend/src/screens/overview/context/ScrollHandlerContext.tsx`
- `frontend/src/screens/overview/hooks/useOverviewLayoutStore.ts`

### 1.3 Reports tab
- `frontend/src/screens/overview/screens/reports/Reports.screen.tsx`
- `frontend/src/screens/overview/screens/reports/components/CompactDateRangeSelector.tsx`
- `frontend/src/screens/overview/screens/reports/components/RevenueChart/RevenueChart.tsx`
- `frontend/src/screens/overview/screens/reports/components/AuditionChart/AuditionChart.tsx`
- `frontend/src/screens/overview/screens/reports/components/RevenueBySourceChart/RevenueBySourceChart.tsx`
- `frontend/src/screens/overview/screens/reports/components/RevenueByCategoryChart/RevenueByCategoryChart.tsx`
- `frontend/src/screens/overview/screens/reports/utils/formatPercentage.ts`
- `frontend/src/screens/overview/screens/reports/utils/colorTints.ts`
- `frontend/src/screens/overview/screens/reports/utils/projectCategoryLabels.ts`

### 1.4 Inbox tab (renamed to Activities in voxkit)
- `frontend/src/screens/overview/screens/inbox/Inbox.screen.tsx`
- `frontend/src/screens/overview/screens/inbox/components/InboxItem.tsx`
- `frontend/src/screens/overview/screens/inbox/components/DetailRow.tsx`
- `frontend/src/screens/overview/screens/inbox/components/AuditionItem.tsx`
- `frontend/src/screens/overview/screens/inbox/components/JobItem.tsx`
- `frontend/src/screens/overview/screens/inbox/components/InvoiceItem.tsx`
- `frontend/src/screens/overview/screens/inbox/components/UsageRightItem.tsx`

### 1.5 GraphQL operations used by source
- `OverviewTabBar.graphql`
- `Inbox.screen.graphql`
- `RevenueChart.graphql`
- `AuditionChart.graphql`
- `RevenueBySourceChart.graphql`
- `RevenueByCategoryChart.graphql`

## 2. Constraints and compatibility gaps in voxkit

1. Backend naming differs:
- source app uses `inboxActions` / `archiveInboxAction`
- voxkit exposes `activities` / `archiveActivity`

2. Missing in current voxkit frontend:
- i18n runtime
- toast runtime
- chart components/runtime
- overview domain UI and data operations

3. Existing tab shell already implemented in voxkit:
- `resources/js/routes/home/components/OverviewScreenTabs/OverviewScreenTabs.tsx`

4. Target policy:
- Implement behavior on Capacitor route only for now
- Keep web route untouched except neutral foundation wiring

## 3. Execution plan (deep, review-gated)

Each major step ends with:
- smallest relevant check(s)
- summary + review stop
- explicit approval before next step

---

## Step 0: Foundation readiness (major)

Goal: prepare platform capabilities needed for parity port without coupling to this single screen.

### 0.1 Chart runtime decision and baseline wiring

Recommended: `react-chartjs-2` + `chart.js` (required peer dependency)

Why this option:
1. Aligns with selected chart stack for this project.
2. Supports line and horizontal bar charts needed by the overview.
3. Can be tuned for better performance with explicit module registration and reduced parsing/animation overhead.

Official references:
- react-chartjs-2 docs: https://react-chartjs-2.js.org/
- Chart.js docs: https://www.chartjs.org/docs/latest/

Implementation pattern:
1. Install and use `react-chartjs-2` as the React API layer.
2. Install `chart.js` as required runtime engine.
3. Register only needed Chart.js modules (no blanket auto-registration in final implementation).
4. Use `Bar` with `indexAxis: "y"` to replicate horizontal bars.
5. Apply performance defaults in wrappers:
- `parsing: false`
- `normalized: true`
- reduced-motion aware animation settings
- decimation for line charts where applicable

Files planned:
- `resources/js/routes/home/components/OverviewScreenTabs/reports/charts/OverviewLineChart.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/reports/charts/OverviewHorizontalBarChart.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/reports/charts/chartRegistry.ts`

Checks:
- `npm run typecheck`
- chart Storybook smoke story

Review gate:
- confirm chart library choice and visual baseline before continuing

### 0.2 Swipe behavior parity tuning (tab swipe + scroll coexistence)

Runtime: keep `motion` (already used)

Official reference:
- Motion drag in React: https://motion.dev/docs/react-drag

Parity behavior model:
1. Horizontal drag only on tab panel track (`drag="x"`).
2. Keep vertical scroll behavior in panel content (`touch-pan-y`).
3. Resolve tab switch by distance + velocity thresholds.
4. Spring settle to active tab on cancellation.
5. Reduce/disable animation with reduced-motion preference.
6. On tab press and swipe start, trigger header visibility reset (show header).

Files planned:
- extend `resources/js/routes/home/components/OverviewScreenTabs/OverviewScreenTabs.tsx`
- keep threshold logic in `resources/js/routes/home/components/OverviewScreenTabs/overviewScreenTabsSwipe.ts`

Checks:
- focused tab gesture tests
- manual Capacitor viewport pass in Storybook

Review gate:
- confirm swipe feel before screen integration

### 0.3 i18n baseline

Recommended deps:
- `i18next`
- `react-i18next`

Files planned:
- `resources/js/lib/i18n.ts`
- `resources/js/locales/en/overview.json`
- app wiring in `resources/js/app.tsx`

Notes:
- start with overview namespace only
- no full localization rollout in this step

Checks:
- `npm run typecheck`
- one translated label renders in Storybook

Review gate:
- approve i18n bootstrapping scope

### 0.4 Toast baseline

Recommended dep:
- `sonner`

Files planned:
- `resources/js/components/ui/Toaster.tsx`
- provider/wiring in root app layout entry point

Checks:
- story/demo trigger for success + error toast
- `npm run typecheck`

Review gate:
- approve toast UX baseline

### 0.5 Shared time/format helpers

Goal:
- replace RN-specific time utilities with deterministic web helpers

Files planned:
- `resources/js/routes/home/components/OverviewScreenTabs/lib/relativeTime.ts`
- `resources/js/routes/home/components/OverviewScreenTabs/lib/formatters.ts`

Checks:
- focused unit tests

Review gate:
- validate copy/format parity

---

## Step 1: Data contract port (major)

Goal: establish all typed GraphQL operations needed by Reports + Activities.

Important adaptation:
- map inbox operations to activities schema in voxkit

Files planned (co-located):
- `resources/js/routes/home/components/OverviewScreenTabs/activities/ActivitiesTabBadge.graphql`
- `resources/js/routes/home/components/OverviewScreenTabs/activities/Activities.screen.graphql`
- `resources/js/routes/home/components/OverviewScreenTabs/reports/RevenueChart.graphql`
- `resources/js/routes/home/components/OverviewScreenTabs/reports/AuditionChart.graphql`
- `resources/js/routes/home/components/OverviewScreenTabs/reports/RevenueBySourceChart.graphql`
- `resources/js/routes/home/components/OverviewScreenTabs/reports/RevenueByCategoryChart.graphql`

Checks:
- `npm run codegen`
- `npm run typecheck`

Review gate:
- approve operation shapes and enum usage before any UI render work

---

## Step 2: Small shared state and mapping primitives (major)

Goal: implement state/mapping layer used by both tabs without visual complexity.

Files planned:
- `resources/js/routes/home/components/OverviewScreenTabs/state/OverviewPeriodContext.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/state/useOverviewLayoutStore.ts`
- `resources/js/routes/home/components/OverviewScreenTabs/activities/activityCopyMapper.ts`
- `resources/js/routes/home/components/OverviewScreenTabs/reports/reportTransformers.ts`

Checks:
- unit tests for mappers and transformers

Review gate:
- approve data-to-UI contract behavior

---

## Step 3: Activities leaf components (major)

Goal: port Inbox component family into web React as Activities item cards.

Files planned:
- `resources/js/routes/home/components/OverviewScreenTabs/activities/components/ActivitiesItem.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/activities/components/ActivityDetailRow.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/activities/components/AuditionActivityItem.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/activities/components/JobActivityItem.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/activities/components/InvoiceActivityItem.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/activities/components/UsageRightActivityItem.tsx`

Notes:
- rename user-facing label to Activities
- preserve action affordances (Snooze/Archive), with archive wired later in screen

Checks:
- Storybook variants for each item type
- snapshot-like visual regression pass via stories

Review gate:
- approve item visual parity and copy parity

---

## Step 4: Reports leaf components (major)

Goal: port reports widgets and selector before building the screen shell.

Files planned:
- `resources/js/routes/home/components/OverviewScreenTabs/reports/components/CompactRangeSelector.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/reports/components/RevenueCard.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/reports/components/AuditionCard.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/reports/components/RevenueBySourceCard.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/reports/components/RevenueByCategoryCard.tsx`

Checks:
- Storybook stories for each card + empty/skeleton states
- `npm run typecheck`

Review gate:
- approve chart/card visual parity and range interactions

---

## Step 5: Screen composition (major)

Goal: compose each full tab screen from completed leaf blocks.

Files planned:
- `resources/js/routes/home/components/OverviewScreenTabs/reports/ReportsScreen.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/activities/ActivitiesScreen.tsx`

Behavior:
1. Reports: sticky range selector + scroll content + suspense sections.
2. Activities: list render by `targetable.__typename`, archive mutation, optimistic UX, loading surface.

Checks:
- focused Vitest tests for loading/success/error and archive behavior

Review gate:
- approve complete screen-level behavior before route wiring

---

## Step 6: Capacitor route integration (major)

Goal: swap placeholder home capacitor overview with composed screen shell.

File update:
- `resources/js/routes/home/home.route.capacitor.tsx`

Rules:
1. Keep auth gate behavior unchanged.
2. Keep web route untouched.
3. Keep lazy screen boundaries at tab level.

Checks:
- route render tests
- Storybook screen story for capacitor target

Review gate:
- approve route-level integration before final tab pass

---

## Step 7: Final tabs integration (major)

Goal: connect real Reports/Activities screens to existing tab component.

Files updated:
- `resources/js/routes/home/components/OverviewScreenTabs/OverviewScreenTabs.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/types.ts`
- tab stories/tests

Behavior:
1. Tabs = Reports + Activities
2. Activities tab badge count from activities paginator total
3. Keep swipe, lazy mount, suspense fallback, and error boundary model
4. Ensure header reveal hook on tab press/swipe start parity

Checks:
- tab integration tests
- manual motion + swipe pass in mobile viewport

Review gate:
- approve tab interaction parity

---

## Step 8: Parity QA and hardening (final)

Goal: close known parity gaps and ship safely.

Checklist:
1. Visual parity across header, tab bar, selector, cards, activity rows.
2. Data parity for count badges and chart period transforms.
3. Interaction parity for swipe, tab press, archive flow.
4. Error fallback parity and retry behavior.
5. Performance sanity: no eager loading of inactive tabs.

Checks:
- `npm run test -- <overview-related tests>`
- `npm run typecheck`

Deliverable:
- final delta list (what is intentionally different from RN and why)

## 4. Approval-required decisions before coding starts

1. Add dependencies: `react-chartjs-2` + `chart.js` for chart parity.
2. Add dependencies: `i18next`, `react-i18next` for i18n baseline.
3. Add dependency: `sonner` for toast baseline.
4. Keep gesture layer on existing `motion` only (no new gesture library).
5. Use `activities` schema as the canonical replacement for inbox data in voxkit.
6. Keep this rollout capacitor-only in route behavior for now.

## 5. Deviation log template (fill during implementation)

Use this section during make/review stops:

1. Deviation:
2. Source behavior:
3. Implemented behavior:
4. Reason:
5. Risk/tradeoff:
6. User approval status:
