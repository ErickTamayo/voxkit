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

### 1.6 Native navigation/layout surfaces coupled to Overview
- `frontend/src/navigation/RootNavigator.tsx`
- `frontend/src/navigation/components/OverviewLayout.tsx`
- `frontend/src/navigation/components/MenuDrawerContent/MenuDrawerContent.tsx`
- `frontend/src/navigation/components/SearchDetailLayout.tsx`
- `frontend/src/screens/search/SearchScreen/Search.screen.tsx`
- `frontend/src/screens/overview/components/OverviewHeader.tsx`

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

## 2.1 Audit delta (current status vs source parity)

### Implemented / mostly aligned
1. Reports + Activities tabs with lazy loading, swipe, suspense, and error boundary.
2. Reports/Activities leaf components and GraphQL data flows.
3. Activities archive mutation with optimistic response and toast feedback.
4. Safe-area foundation updated to top + x at root, bottom inset opt-in per scroll/list.

### Partial
1. Tab shell visual structure is close, but header-coupled behavior is still missing.
2. Activities badge query exists but tab badge parity is not yet wired to final UX.

### Missing (high impact)
1. Animated Overview header container with scroll-triggered hide/reveal.
2. Header action parity:
- search action opening Search flow
- menu action opening right-side drawer
- avatar/header action opening Settings modal flow
3. Right drawer surface (position right, overlay behavior, close affordances) for Capacitor target.
4. Search modal flow (full-screen style entry + detail navigation within search flow).
5. Route-level modal orchestration for Settings/Search/Create-style flows used by header actions.

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

## Step 7: Header + Tab Coupling Parity (major)

Goal: finish Overview interaction parity between header, tab bar, and tab content.

Files updated:
- `resources/js/routes/home/components/OverviewHomeHeader.tsx` (new)
- `resources/js/routes/home/components/overviewHeaderAnimation.ts` (new)
- `resources/js/routes/home/components/OverviewScreenTabs/state/useOverviewLayoutStore.ts`
- `resources/js/routes/home/home.route.capacitor.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/OverviewScreenTabs.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/types.ts`
- tab stories/tests

Behavior:
1. Introduce dedicated Overview header component (avatar/greeter + actions).
2. Header is measured and coupled with tab/content layout offsets.
3. Scroll-triggered hide/reveal behavior is restored.
4. Tab press + swipe start force header reveal (parity behavior).
5. Activities tab badge count renders from activities paginator total.
6. Keep swipe, lazy mount, suspense fallback, and error boundary model.

Checks:
- tab integration tests
- focused header animation tests (store/behavior level where deterministic)
- manual motion + swipe pass in mobile viewport

Review gate:
- approve header + tab coupling parity before navigation overlays

---

## Step 8: Navigation Overlay Parity (major)

Goal: port navigation surfaces that Overview header actions depend on.

Files planned:
- `resources/js/routes/search/Search.route.capacitor.tsx` (new)
- `resources/js/routes/search/components/*` (new, scoped to MVP parity)
- `resources/js/components/ui/right-drawer/*` or route-level drawer surface (new)
- `resources/js/app.capacitor.tsx` or route-shell composition updates (target-scoped)
- `resources/js/routes/home/home.route.capacitor.tsx` (header action wiring)

Behavior:
1. Search action opens a Capacitor search surface with close/back behavior.
2. Menu action opens right-side drawer with overlay and close affordances.
3. Avatar/header action opens Settings modal-style surface.
4. Keep web routing/surfaces untouched.

Checks:
- `npm run typecheck`
- focused interaction tests (open/close states and route transitions)
- Storybook route stories for drawer + search surface

Review gate:
- approve navigation overlay parity before final QA

---

### Step 8A: Temporary Navigation Contract Mapping (implemented stubs)

Goal: make all outbound interactions explicit now, even before real routes/overlays are wired.

Implemented contracts (current behavior = `console.info`):
1. Header settings action -> `/settings`
2. Header search action -> `/search`
3. Header menu action -> `drawer:right`
4. Activities audition row press -> `/auditions/:auditionId`
5. Activities job row press -> `/jobs/:jobId`
6. Activities invoice row press -> `/invoices/:invoiceId`
7. Activities usage right row press -> `/usage-rights/:usageRightId`
8. Activities snooze action -> temporary payload log (`activityId`, `targetType`, `targetId`)
9. Activities archive action -> real mutation + toast (already wired)
10. Search result detail contracts reserved (temporary stubs):
- `/agents/:agentId`
- `/clients/:clientId`
- `/expenses/:expenseId`
- `/platforms/:platformId`
- `/notes/:noteId`

Files:
- `resources/js/routes/home/components/overviewNavigation.tsx`
- `resources/js/routes/home/components/OverviewHomeHeader.tsx`
- `resources/js/routes/home/home.route.capacitor.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/activities/ActivitiesScreen.tsx`
- `resources/js/routes/home/components/OverviewScreenTabs/activities/components/ActivitiesItem.tsx`

Notes:
- This keeps canonical path contracts stable while route/modal/drawer wiring is still pending.
- Replace stubs incrementally with real route transitions and overlay state in Step 8 proper.

---

### Step 8B: Header Overlay Wiring (implemented)

Goal: replace header `console.info` stubs with real Capacitor-only overlay surfaces while preserving detail-route stubs.

Implemented:
1. Search header action opens `OverviewSearchOverlay`.
2. Settings header action opens `OverviewSettingsOverlay`.
3. Menu header action opens `OverviewMenuDrawerOverlay` (right-side drawer with overlay + close).
4. Overlay orchestration is route-scoped (`none | search | settings | menu`), one surface at a time.
5. Activities detail actions remain contract stubs for now.

Files:
- `resources/js/routes/home/home.route.capacitor.tsx`
- `resources/js/routes/home/components/overlays/OverviewSearchOverlay.tsx`
- `resources/js/routes/home/components/overlays/OverviewSettingsOverlay.tsx`
- `resources/js/routes/home/components/overlays/OverviewMenuDrawerOverlay.tsx`
- `resources/js/routes/home/home.route.capacitor.stories.tsx`

---

## Step 9: Parity QA and hardening (final)

Goal: close remaining gaps and ship safely.

Checklist:
1. Visual parity across header, tab bar, selector, cards, activity rows.
2. Data parity for count badges, chart transforms, and search result rendering.
3. Interaction parity for swipe, tab press, archive flow, drawer/search/settings actions.
4. Error fallback parity and retry behavior across tabs and overlays.
5. Performance sanity: no eager loading of inactive tabs; no blocking reflow loops.

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
7. Approve Capacitor-only right drawer + search/modal overlay orchestration strategy (without changing web routing).

## 5. Deviation log template (fill during implementation)

Use this section during make/review stops:

1. Deviation:
2. Source behavior:
3. Implemented behavior:
4. Reason:
5. Risk/tradeoff:
6. User approval status:
