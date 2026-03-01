import { useEffect, useState, type ComponentProps, type FC } from "react";
import type { Meta, StoryObj } from "@storybook/react-vite";
import type { MockedResponse } from "@apollo/client/testing";
import { MockedProvider } from "@apollo/client/testing/react";
import {
    ActivityAction,
    ActivityTrigger,
    AuditionStatus,
    CompactRange,
    ProjectCategory,
    QueryActivitiesWhereColumn,
    RevenueSourceType,
    SqlOperator,
} from "@/graphql/types";
import { OverviewActivitiesTabBadgeDocument } from "@/routes/home/components/OverviewScreenTabs/activities/ActivitiesTabBadge.graphql.ts";
import type {
    OverviewActivitiesTabBadgeQuery,
    OverviewActivitiesTabBadgeQueryVariables,
} from "@/routes/home/components/OverviewScreenTabs/activities/ActivitiesTabBadge.graphql.ts";
import {
    OverviewActivitiesSectionDocument,
    OverviewArchiveActivityDocument,
    type OverviewActivitiesSectionQuery,
    type OverviewActivitiesSectionQueryVariables,
    type OverviewArchiveActivityMutation,
    type OverviewArchiveActivityMutationVariables,
} from "@/routes/home/components/OverviewScreenTabs/activities/ActivitiesScreen.graphql.ts";
import {
    OverviewAuditionChartDocument,
    type OverviewAuditionChartQuery,
    type OverviewAuditionChartQueryVariables,
} from "@/routes/home/components/OverviewScreenTabs/reports/AuditionChart.graphql.ts";
import {
    OverviewRevenueByCategoryDocument,
    type OverviewRevenueByCategoryQuery,
    type OverviewRevenueByCategoryQueryVariables,
} from "@/routes/home/components/OverviewScreenTabs/reports/RevenueByCategoryChart.graphql.ts";
import {
    OverviewRevenueBySourceDocument,
    type OverviewRevenueBySourceQuery,
    type OverviewRevenueBySourceQueryVariables,
} from "@/routes/home/components/OverviewScreenTabs/reports/RevenueBySourceChart.graphql.ts";
import {
    OverviewRevenueChartDocument,
    type OverviewRevenueChartQuery,
    type OverviewRevenueChartQueryVariables,
} from "@/routes/home/components/OverviewScreenTabs/reports/RevenueChart.graphql.ts";
import HomeRouteCapacitor from "@/routes/home/home.route.capacitor";
import { useSessionStore } from "@/stores/sessionStore";
import { meQuery } from "@/tests/graphql/MeQuery";

const DAY_MS = 86_400_000;
const REFERENCE_END = Date.UTC(2026, 0, 29);
const REPORT_PERIODS: CompactRange[] = [
    CompactRange.OneWeek,
    CompactRange.FourWeeks,
    CompactRange.MonthToDate,
    CompactRange.QuarterToDate,
    CompactRange.YearToDate,
    CompactRange.OneYear,
    CompactRange.AllTime,
];
const OPEN_ACTIVITIES_WHERE = {
    column: QueryActivitiesWhereColumn.Action,
    operator: SqlOperator.IsNull,
};

function getPeriodWindowDays(period: CompactRange): number {
    switch (period) {
        case CompactRange.OneWeek:
            return 7;
        case CompactRange.FourWeeks:
            return 28;
        case CompactRange.MonthToDate:
            return 29;
        case CompactRange.QuarterToDate:
            return 90;
        case CompactRange.YearToDate:
            return 365;
        case CompactRange.OneYear:
            return 365;
        case CompactRange.AllTime:
            return 730;
    }
}

function getPeriodMultiplier(period: CompactRange): number {
    switch (period) {
        case CompactRange.OneWeek:
            return 0.5;
        case CompactRange.FourWeeks:
            return 1;
        case CompactRange.MonthToDate:
            return 0.85;
        case CompactRange.QuarterToDate:
            return 1.35;
        case CompactRange.YearToDate:
            return 1.8;
        case CompactRange.OneYear:
            return 2.1;
        case CompactRange.AllTime:
            return 2.6;
    }
}

function createOverviewChartPoints(input: {
    baseValues: number[];
    periodEnd: number;
    periodStart: number;
}): Array<{ __typename: "ChartPoint"; timestamp: number; value: number }> {
    const chartSpan = input.periodEnd - input.periodStart;
    const lastIndex = Math.max(1, input.baseValues.length - 1);

    return input.baseValues.map((value, index) => {
        const ratio = index / lastIndex;

        return {
            __typename: "ChartPoint",
            timestamp: Math.round(input.periodStart + chartSpan * ratio),
            value,
        };
    });
}

function createRevenueChartMock(
    period: CompactRange,
): MockedResponse<OverviewRevenueChartQuery, OverviewRevenueChartQueryVariables> {
    const windowDays = getPeriodWindowDays(period);
    const multiplier = getPeriodMultiplier(period);
    const periodEnd = REFERENCE_END;
    const periodStart = periodEnd - windowDays * DAY_MS;
    const totalCents = Math.round(2_624_500 * multiplier);
    const pendingCents = Math.round(761_200 * multiplier);
    const chartValues = [390_000, 540_000, 475_000, 628_000, 592_000].map((value) => {
        return Math.round(value * multiplier);
    });

    return {
        request: {
            query: OverviewRevenueChartDocument,
            variables: { period },
        },
        result: {
            data: {
                __typename: "Query",
                revenueMetrics: {
                    __typename: "RevenueMetricsResponse",
                    baseCurrency: "USD",
                    period: {
                        __typename: "DateRange",
                        start: periodStart,
                        end: periodEnd,
                    },
                    metrics: {
                        __typename: "RevenueMetrics",
                        current: {
                            __typename: "RevenueMetricsSnapshot",
                            trend_percentage: 18.2,
                            total: {
                                __typename: "Money",
                                amount_cents: totalCents,
                                currency: "USD",
                            },
                            comparison_total: {
                                __typename: "Money",
                                amount_cents: Math.round(totalCents * 0.83),
                                currency: "USD",
                            },
                        },
                        pipeline: {
                            __typename: "RevenuePipelineMetrics",
                            total: {
                                __typename: "Money",
                                amount_cents: Math.round(totalCents * 0.2),
                                currency: "USD",
                            },
                        },
                        in_flight: {
                            __typename: "RevenuePipelineMetrics",
                            total: {
                                __typename: "Money",
                                amount_cents: pendingCents,
                                currency: "USD",
                            },
                        },
                    },
                },
                revenueChart: {
                    __typename: "RevenueChartResponse",
                    baseCurrency: "USD",
                    effectiveWindow: {
                        __typename: "DateRangeWindow",
                        start: periodStart,
                        end: periodEnd,
                        daysInRange: windowDays,
                        wasExpanded: false,
                        expansionReason: null,
                    },
                    chart: createOverviewChartPoints({
                        periodStart,
                        periodEnd,
                        baseValues: chartValues,
                    }),
                },
            },
        },
        maxUsageCount: 12,
    };
}

function createAuditionChartMock(
    period: CompactRange,
): MockedResponse<OverviewAuditionChartQuery, OverviewAuditionChartQueryVariables> {
    const windowDays = getPeriodWindowDays(period);
    const multiplier = getPeriodMultiplier(period);
    const periodEnd = REFERENCE_END;
    const periodStart = periodEnd - windowDays * DAY_MS;
    const totalAuditions = Math.round(52 * multiplier);
    const chartValues = [8, 13, 11, 16, 14].map((value) => {
        return Math.round(value * multiplier);
    });

    return {
        request: {
            query: OverviewAuditionChartDocument,
            variables: { period },
        },
        result: {
            data: {
                __typename: "Query",
                auditionMetrics: {
                    __typename: "AuditionMetricsResponse",
                    metrics: {
                        __typename: "AuditionMetrics",
                        booking_rate: 21.7,
                        current: {
                            __typename: "AuditionMetricsSnapshot",
                            total: totalAuditions,
                            comparison_total: Math.round(totalAuditions * 0.89),
                            trend_percentage: 11.4,
                        },
                    },
                    period: {
                        __typename: "DateRange",
                        start: periodStart,
                        end: periodEnd,
                    },
                },
                auditionChart: {
                    __typename: "AuditionChartResponse",
                    chart: createOverviewChartPoints({
                        periodStart,
                        periodEnd,
                        baseValues: chartValues,
                    }),
                    effectiveWindow: {
                        __typename: "DateRangeWindow",
                        start: periodStart,
                        end: periodEnd,
                        daysInRange: windowDays,
                        wasExpanded: false,
                        expansionReason: null,
                    },
                    range: {
                        __typename: "CompactRangeValue",
                        value: period,
                    },
                },
            },
        },
        maxUsageCount: 12,
    };
}

function createRevenueBySourceMock(
    period: CompactRange,
): MockedResponse<OverviewRevenueBySourceQuery, OverviewRevenueBySourceQueryVariables> {
    const windowDays = getPeriodWindowDays(period);
    const periodEnd = REFERENCE_END;
    const periodStart = periodEnd - windowDays * DAY_MS;

    return {
        request: {
            query: OverviewRevenueBySourceDocument,
            variables: { period },
        },
        result: {
            data: {
                __typename: "Query",
                revenueBySource: {
                    __typename: "RevenueBySourceResponse",
                    baseCurrency: "USD",
                    period: {
                        __typename: "DateRange",
                        start: periodStart,
                        end: periodEnd,
                    },
                    sources: [
                        {
                            __typename: "RevenueBySourceEntry",
                            source_type: RevenueSourceType.Direct,
                            source_name: "Direct",
                            percentage_of_total: 44,
                            paid: {
                                __typename: "Money",
                                amount_cents: 1_100_000,
                                currency: "USD",
                            },
                            in_flight: {
                                __typename: "Money",
                                amount_cents: 140_000,
                                currency: "USD",
                            },
                        },
                        {
                            __typename: "RevenueBySourceEntry",
                            source_type: RevenueSourceType.Agent,
                            source_name: "Agent",
                            percentage_of_total: 31,
                            paid: {
                                __typename: "Money",
                                amount_cents: 780_000,
                                currency: "USD",
                            },
                            in_flight: {
                                __typename: "Money",
                                amount_cents: 120_000,
                                currency: "USD",
                            },
                        },
                        {
                            __typename: "RevenueBySourceEntry",
                            source_type: RevenueSourceType.Platform,
                            source_name: "Platform",
                            percentage_of_total: 18,
                            paid: {
                                __typename: "Money",
                                amount_cents: 460_000,
                                currency: "USD",
                            },
                            in_flight: {
                                __typename: "Money",
                                amount_cents: 90_000,
                                currency: "USD",
                            },
                        },
                        {
                            __typename: "RevenueBySourceEntry",
                            source_type: RevenueSourceType.Unknown,
                            source_name: "Other",
                            percentage_of_total: 7,
                            paid: {
                                __typename: "Money",
                                amount_cents: 180_000,
                                currency: "USD",
                            },
                            in_flight: {
                                __typename: "Money",
                                amount_cents: 45_000,
                                currency: "USD",
                            },
                        },
                    ],
                },
            },
        },
        maxUsageCount: 12,
    };
}

function createRevenueByCategoryMock(
    period: CompactRange,
): MockedResponse<OverviewRevenueByCategoryQuery, OverviewRevenueByCategoryQueryVariables> {
    const windowDays = getPeriodWindowDays(period);
    const periodEnd = REFERENCE_END;
    const periodStart = periodEnd - windowDays * DAY_MS;

    return {
        request: {
            query: OverviewRevenueByCategoryDocument,
            variables: { period },
        },
        result: {
            data: {
                __typename: "Query",
                revenueByCategory: {
                    __typename: "RevenueByCategoryResponse",
                    baseCurrency: "USD",
                    period: {
                        __typename: "DateRange",
                        start: periodStart,
                        end: periodEnd,
                    },
                    categories: [
                        {
                            __typename: "RevenueByCategoryEntry",
                            category: ProjectCategory.Commercial,
                            percentage_of_total: 41,
                            paid: {
                                __typename: "Money",
                                amount_cents: 1_000_000,
                                currency: "USD",
                            },
                            in_flight: {
                                __typename: "Money",
                                amount_cents: 140_000,
                                currency: "USD",
                            },
                        },
                        {
                            __typename: "RevenueByCategoryEntry",
                            category: ProjectCategory.Elearning,
                            percentage_of_total: 27,
                            paid: {
                                __typename: "Money",
                                amount_cents: 680_000,
                                currency: "USD",
                            },
                            in_flight: {
                                __typename: "Money",
                                amount_cents: 110_000,
                                currency: "USD",
                            },
                        },
                        {
                            __typename: "RevenueByCategoryEntry",
                            category: ProjectCategory.Trailer,
                            percentage_of_total: 21,
                            paid: {
                                __typename: "Money",
                                amount_cents: 530_000,
                                currency: "USD",
                            },
                            in_flight: {
                                __typename: "Money",
                                amount_cents: 80_000,
                                currency: "USD",
                            },
                        },
                        {
                            __typename: "RevenueByCategoryEntry",
                            category: ProjectCategory.Animation,
                            percentage_of_total: 11,
                            paid: {
                                __typename: "Money",
                                amount_cents: 280_000,
                                currency: "USD",
                            },
                            in_flight: {
                                __typename: "Money",
                                amount_cents: 35_000,
                                currency: "USD",
                            },
                        },
                    ],
                },
            },
        },
        maxUsageCount: 12,
    };
}

const ACTIVITIES_DATA: OverviewActivitiesSectionQuery["activities"]["data"] = [
    {
        __typename: "Activity",
        id: "activity-audition-1",
        trigger: ActivityTrigger.AuditionResponseDue,
        action: null,
        created_at: REFERENCE_END - DAY_MS,
        targetable: {
            __typename: "Audition",
            id: "audition-1",
            response_deadline: REFERENCE_END - DAY_MS * 2,
            project_deadline: null,
            project_title: "Nike Campaign VO",
            status: AuditionStatus.Callback,
            sourceable: {
                __typename: "Platform",
                id: "platform-1",
                name: "Voices.com",
            },
        },
        notes: {
            __typename: "NotePaginator",
            paginatorInfo: {
                __typename: "PaginatorInfo",
                count: 0,
            },
        },
    },
    {
        __typename: "Activity",
        id: "activity-job-1",
        trigger: ActivityTrigger.JobDeliveryDue,
        action: null,
        created_at: REFERENCE_END - DAY_MS * 2,
        targetable: {
            __typename: "Job",
            id: "job-1",
            session_date: REFERENCE_END + DAY_MS,
            delivery_deadline: REFERENCE_END - DAY_MS * 3,
            project_title: "Trailer Narration",
        },
        notes: {
            __typename: "NotePaginator",
            paginatorInfo: {
                __typename: "PaginatorInfo",
                count: 1,
            },
        },
    },
    {
        __typename: "Activity",
        id: "activity-invoice-1",
        trigger: ActivityTrigger.InvoiceOverdue,
        action: null,
        created_at: REFERENCE_END - DAY_MS * 4,
        targetable: {
            __typename: "Invoice",
            id: "invoice-1",
            job: {
                __typename: "Job",
                project_title: "Corporate Explainer",
            },
            total: {
                __typename: "MonetaryAmount",
                original: {
                    __typename: "Money",
                    currency: "USD",
                    amount_cents: 287_000,
                },
            },
        },
        notes: {
            __typename: "NotePaginator",
            paginatorInfo: {
                __typename: "PaginatorInfo",
                count: 0,
            },
        },
    },
    {
        __typename: "Activity",
        id: "activity-usage-right-1",
        trigger: ActivityTrigger.UsageRightsExpiring,
        action: null,
        created_at: REFERENCE_END - DAY_MS * 5,
        targetable: {
            __typename: "UsageRight",
            id: "usage-right-1",
            expiration_date: REFERENCE_END + DAY_MS * 7,
            usable: {
                __typename: "Audition",
                id: "audition-2",
                project_title: "Tech Product Launch",
            },
        },
        notes: {
            __typename: "NotePaginator",
            paginatorInfo: {
                __typename: "PaginatorInfo",
                count: 0,
            },
        },
    },
];

const ACTIVITIES_RESULT: OverviewActivitiesSectionQuery = {
    __typename: "Query",
    activities: {
        __typename: "ActivityPaginator",
        paginatorInfo: {
            __typename: "PaginatorInfo",
            total: ACTIVITIES_DATA.length,
        },
        data: ACTIVITIES_DATA,
    },
};

const ACTIVITIES_TAB_BADGE_RESULT: OverviewActivitiesTabBadgeQuery = {
    __typename: "Query",
    activities: {
        __typename: "ActivityPaginator",
        paginatorInfo: {
            __typename: "PaginatorInfo",
            total: ACTIVITIES_DATA.length,
        },
    },
};

function createActivitiesSectionMock(): MockedResponse<
    OverviewActivitiesSectionQuery,
    OverviewActivitiesSectionQueryVariables
> {
    return {
        request: {
            query: OverviewActivitiesSectionDocument,
            variables: {
                where: OPEN_ACTIVITIES_WHERE,
            },
        },
        result: {
            data: ACTIVITIES_RESULT,
        },
        maxUsageCount: 20,
    };
}

function createActivitiesTabBadgeMock(): MockedResponse<
    OverviewActivitiesTabBadgeQuery,
    OverviewActivitiesTabBadgeQueryVariables
> {
    return {
        request: {
            query: OverviewActivitiesTabBadgeDocument,
            variables: {
                where: OPEN_ACTIVITIES_WHERE,
            },
        },
        result: {
            data: ACTIVITIES_TAB_BADGE_RESULT,
        },
        maxUsageCount: 20,
    };
}

function createArchiveActivityMock(): MockedResponse<
    OverviewArchiveActivityMutation,
    OverviewArchiveActivityMutationVariables
> {
    return {
        request: {
            query: OverviewArchiveActivityDocument,
            variables: (variables) => {
                return typeof variables?.input?.id === "string";
            },
        },
        result: (variables) => {
            return {
                data: {
                    __typename: "Mutation",
                    archiveActivity: {
                        __typename: "Activity",
                        id: variables.input.id,
                        action: ActivityAction.Archived,
                    },
                },
            };
        },
        maxUsageCount: 20,
    };
}

const ROUTE_MOCKS: ReadonlyArray<MockedResponse> = [
    meQuery({
        me: {
            id: "storybook-user-1",
            name: "Erick",
            email: "erick@example.com",
            email_verified_at: REFERENCE_END,
        },
    }),
    ...REPORT_PERIODS.flatMap((period) => {
        return [
            createRevenueChartMock(period),
            createAuditionChartMock(period),
            createRevenueBySourceMock(period),
            createRevenueByCategoryMock(period),
        ];
    }),
    createActivitiesSectionMock(),
    createActivitiesTabBadgeMock(),
    createArchiveActivityMock(),
];

type HomeRouteCapacitorStoryProps = Pick<
    ComponentProps<typeof HomeRouteCapacitor>,
    "initialOverlay"
>;

const HomeCapacitorOverviewRouteStory: FC<HomeRouteCapacitorStoryProps> = ({
    initialOverlay = "none",
}) => {
    const [previousStatus] = useState(() => {
        const priorStatus = useSessionStore.getState().status;
        if (priorStatus !== "authenticated") {
            useSessionStore.getState().setStatus("authenticated");
        }

        return priorStatus;
    });

    useEffect(() => {
        return () => {
            useSessionStore.getState().setStatus(previousStatus);
        };
    }, [previousStatus]);

    return (
        <MockedProvider mocks={ROUTE_MOCKS}>
            <HomeRouteCapacitor initialOverlay={initialOverlay} />
        </MockedProvider>
    );
};

const meta = {
    title: "Screens/Home/Capacitor/Overview Route",
    component: HomeCapacitorOverviewRouteStory,
    args: {
        initialOverlay: "none",
    },
    argTypes: {
        initialOverlay: {
            control: "select",
            options: ["none", "search", "menu", "settings"],
        },
    },
    parameters: {
        layout: "fullscreen",
    },
} satisfies Meta<typeof HomeCapacitorOverviewRouteStory>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Authenticated: Story = {
    globals: {
        platformTarget: "capacitor",
        safeAreaPreset: "iphone",
    },
    parameters: {
        viewport: {
            defaultViewport: "mobileSheet",
        },
    },
};

export const SearchOverlayOpen: Story = {
    args: {
        initialOverlay: "search",
    },
    globals: Authenticated.globals,
    parameters: Authenticated.parameters,
};

export const MenuOverlayOpen: Story = {
    args: {
        initialOverlay: "menu",
    },
    globals: Authenticated.globals,
    parameters: Authenticated.parameters,
};

export const SettingsOverlayOpen: Story = {
    args: {
        initialOverlay: "settings",
    },
    globals: Authenticated.globals,
    parameters: Authenticated.parameters,
};
