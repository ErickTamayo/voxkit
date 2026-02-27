import type {
    OverviewActivitiesSectionQuery,
} from "@/routes/home/components/OverviewScreenTabs/activities/ActivitiesScreen.graphql.ts";

type OverviewActivityEntry = OverviewActivitiesSectionQuery["activities"]["data"][number];
type OverviewActivityTarget = NonNullable<OverviewActivityEntry["targetable"]>;
type AuditionActivityTarget = Extract<OverviewActivityTarget, { __typename: "Audition" }>;
type JobActivityTarget = Extract<OverviewActivityTarget, { __typename: "Job" }>;
type InvoiceActivityTarget = Extract<OverviewActivityTarget, { __typename: "Invoice" }>;
type UsageRightActivityTarget = Extract<OverviewActivityTarget, { __typename: "UsageRight" }>;

export type {
    AuditionActivityTarget,
    InvoiceActivityTarget,
    JobActivityTarget,
    OverviewActivityEntry,
    OverviewActivityTarget,
    UsageRightActivityTarget,
};
