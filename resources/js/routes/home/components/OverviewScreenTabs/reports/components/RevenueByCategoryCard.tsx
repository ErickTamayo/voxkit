import type { FC } from "react";
import { useSuspenseQuery } from "@apollo/client/react";
import { useTranslation } from "react-i18next";
import { CompactRange } from "@/graphql/types";
import {
    OverviewRevenueByCategoryDocument,
} from "@/routes/home/components/OverviewScreenTabs/reports/RevenueByCategoryChart.graphql.ts";
import { OverviewHorizontalBarChart } from "@/routes/home/components/OverviewScreenTabs/reports/charts/OverviewHorizontalBarChart";
import {
    formatOverviewChartDisplayRange,
    toOverviewRevenueByCategoryBars,
    type OverviewBarDatum,
} from "@/routes/home/components/OverviewScreenTabs/reports/reportTransformers";

interface RevenueByCategoryCardProps {
    period: CompactRange;
}

interface RevenueByCategoryCardViewProps {
    bars: OverviewBarDatum[];
    displayRange: string;
}

const OVERVIEW_BRAND_CHART_COLOR = "#7f56d9";

const RevenueByCategoryCardView: FC<RevenueByCategoryCardViewProps> = ({
    bars,
    displayRange,
}) => {
    const { t } = useTranslation();

    return (
        <section className="space-y-4 px-4 py-5">
            <header className="space-y-1">
                <p className="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                    {t("Top 5 revenue by category")}
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
                        {t("No category revenue in this period.")}
                    </p>
                </div>
            )}
        </section>
    );
};

const RevenueByCategoryCardSkeleton: FC = () => {
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

const RevenueByCategoryCard: FC<RevenueByCategoryCardProps> = ({
    period,
}) => {
    const { data } = useSuspenseQuery(OverviewRevenueByCategoryDocument, {
        variables: {
            period,
        },
    });
    const bars = toOverviewRevenueByCategoryBars({
        baseColor: OVERVIEW_BRAND_CHART_COLOR,
        entries: data.revenueByCategory.categories.map((category) => ({
            category: category.category,
            percentage_of_total: category.percentage_of_total,
        })),
    });
    const displayRange = formatOverviewChartDisplayRange({
        period,
        periodStart: data.revenueByCategory.period.start,
        periodEnd: data.revenueByCategory.period.end,
    });

    return (
        <RevenueByCategoryCardView
            bars={bars}
            displayRange={displayRange}
        />
    );
};

export type {
    RevenueByCategoryCardProps,
    RevenueByCategoryCardViewProps,
};
export {
    RevenueByCategoryCard,
    RevenueByCategoryCardSkeleton,
    RevenueByCategoryCardView,
};
