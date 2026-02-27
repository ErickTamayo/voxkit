import type { FC } from "react";
import { useSuspenseQuery } from "@apollo/client/react";
import { useTranslation } from "react-i18next";
import { CompactRange } from "@/graphql/types";
import {
    OverviewRevenueBySourceDocument,
} from "@/routes/home/components/OverviewScreenTabs/reports/RevenueBySourceChart.graphql.ts";
import { OverviewHorizontalBarChart } from "@/routes/home/components/OverviewScreenTabs/reports/charts/OverviewHorizontalBarChart";
import {
    formatOverviewChartDisplayRange,
    toOverviewRevenueBySourceBars,
    type OverviewBarDatum,
} from "@/routes/home/components/OverviewScreenTabs/reports/reportTransformers";

interface RevenueBySourceCardProps {
    period: CompactRange;
}

interface RevenueBySourceCardViewProps {
    bars: OverviewBarDatum[];
    displayRange: string;
}

const OVERVIEW_BRAND_CHART_COLOR = "#7f56d9";

const RevenueBySourceCardView: FC<RevenueBySourceCardViewProps> = ({
    bars,
    displayRange,
}) => {
    const { t } = useTranslation();

    return (
        <section className="space-y-4 px-4 py-5">
            <header className="space-y-1">
                <p className="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                    {t("Top 5 revenue by source")}
                </p>
                <p className="text-xs text-muted-foreground">{displayRange}</p>
            </header>

            {bars.length > 0 ? (
                <OverviewHorizontalBarChart
                    data={bars}
                    tooltipValueFormatter={(value) => `${Math.round(value)}%`}
                    xAxisTickFormatter={(value) => `${Math.round(value)}%`}
                />
            ) : (
                <div className="rounded-md border border-dashed border-border/80 p-6">
                    <p className="text-sm text-muted-foreground">
                        {t("No source revenue in this period.")}
                    </p>
                </div>
            )}
        </section>
    );
};

const RevenueBySourceCardSkeleton: FC = () => {
    return (
        <section className="space-y-4 px-4 py-5">
            <div className="space-y-2">
                <div className="h-3 w-36 animate-pulse rounded bg-muted" />
                <div className="h-3 w-40 animate-pulse rounded bg-muted" />
            </div>
            <div className="h-60 w-full animate-pulse rounded-lg bg-muted" />
        </section>
    );
};

const RevenueBySourceCard: FC<RevenueBySourceCardProps> = ({
    period,
}) => {
    const { data } = useSuspenseQuery(OverviewRevenueBySourceDocument, {
        variables: {
            period,
        },
    });
    const bars = toOverviewRevenueBySourceBars({
        baseColor: OVERVIEW_BRAND_CHART_COLOR,
        entries: data.revenueBySource.sources.map((source) => ({
            source_name: source.source_name,
            percentage_of_total: source.percentage_of_total,
        })),
    });
    const displayRange = formatOverviewChartDisplayRange({
        period,
        periodStart: data.revenueBySource.period.start,
        periodEnd: data.revenueBySource.period.end,
    });

    return (
        <RevenueBySourceCardView
            bars={bars}
            displayRange={displayRange}
        />
    );
};

export type {
    RevenueBySourceCardProps,
    RevenueBySourceCardViewProps,
};
export {
    RevenueBySourceCard,
    RevenueBySourceCardSkeleton,
    RevenueBySourceCardView,
};
