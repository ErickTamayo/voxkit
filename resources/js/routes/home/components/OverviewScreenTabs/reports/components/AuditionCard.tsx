import type { FC } from "react";
import { useSuspenseQuery } from "@apollo/client/react";
import { TrendingDown, TrendingUp } from "lucide-react";
import { useTranslation } from "react-i18next";
import { CompactRange } from "@/graphql/types";
import {
    formatOverviewInteger,
    formatOverviewPercentage,
} from "@/routes/home/components/OverviewScreenTabs/lib/formatters";
import {
    OverviewAuditionChartDocument,
} from "@/routes/home/components/OverviewScreenTabs/reports/AuditionChart.graphql.ts";
import { OverviewLineChart } from "@/routes/home/components/OverviewScreenTabs/reports/charts/OverviewLineChart";
import {
    formatOverviewChartDisplayRange,
    formatOverviewChartLabel,
} from "@/routes/home/components/OverviewScreenTabs/reports/reportTransformers";

interface AuditionCardProps {
    period: CompactRange;
}

interface AuditionCardChartDatum {
    label: string;
    value: number;
}

interface AuditionCardViewProps {
    bookingRate: number;
    chartData: AuditionCardChartDatum[];
    displayRange: string;
    totalAuditions: number;
    trendPercentage: number;
}

const AuditionCardView: FC<AuditionCardViewProps> = ({
    bookingRate,
    chartData,
    displayRange,
    totalAuditions,
    trendPercentage,
}) => {
    const { t } = useTranslation();
    const isPositiveTrend = trendPercentage >= 0;
    const TrendIcon = isPositiveTrend ? TrendingUp : TrendingDown;
    const trendClassName = isPositiveTrend
        ? "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"
        : "bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300";

    return (
        <section className="space-y-4 px-4 py-5">
            <header className="space-y-1">
                <div className="flex items-start justify-between gap-3">
                    <div className="space-y-1">
                        <p className="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                            {t("Auditions")}
                        </p>
                        <div className="flex items-center gap-2">
                            <p className="text-2xl font-semibold text-foreground">
                                {formatOverviewInteger(totalAuditions)}
                            </p>
                            <span
                                className={`inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-semibold ${trendClassName}`}
                            >
                                <TrendIcon className="size-3" />
                                {trendPercentage > 0 ? "+" : ""}
                                {formatOverviewPercentage(Math.abs(trendPercentage))}
                            </span>
                        </div>
                    </div>

                    <div className="space-y-1 text-right">
                        <p className="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                            {t("Booking rate")}
                        </p>
                        <p className="text-2xl font-medium text-muted-foreground">
                            {formatOverviewPercentage(bookingRate)}
                        </p>
                    </div>
                </div>

                <p className="text-xs text-muted-foreground">{displayRange}</p>
            </header>

            {chartData.length > 0 ? (
                <OverviewLineChart
                    data={chartData}
                    tooltipValueFormatter={(value) => formatOverviewInteger(Math.round(value))}
                    yAxisTickFormatter={(value) => formatOverviewInteger(Math.round(value))}
                />
            ) : (
                <div className="rounded-md border border-dashed border-border/80 p-6">
                    <p className="text-sm text-muted-foreground">
                        {t("No audition points in this period.")}
                    </p>
                </div>
            )}
        </section>
    );
};

const AuditionCardSkeleton: FC = () => {
    return (
        <section className="space-y-4 px-4 py-5">
            <div className="space-y-2">
                <div className="h-3 w-24 animate-pulse rounded bg-muted" />
                <div className="h-8 w-32 animate-pulse rounded bg-muted" />
                <div className="h-3 w-40 animate-pulse rounded bg-muted" />
            </div>
            <div className="h-60 w-full animate-pulse rounded-lg bg-muted" />
        </section>
    );
};

const AuditionCard: FC<AuditionCardProps> = ({
    period,
}) => {
    const { data } = useSuspenseQuery(OverviewAuditionChartDocument, {
        variables: {
            period,
        },
    });
    const totalAuditions = data.auditionMetrics.metrics.current.total;
    const bookingRate = data.auditionMetrics.metrics.booking_rate;
    const trendPercentage = data.auditionMetrics.metrics.current.trend_percentage;
    const displayRange = formatOverviewChartDisplayRange({
        period,
        periodStart: data.auditionMetrics.period.start,
        periodEnd: data.auditionMetrics.period.end,
    });
    const chartData = data.auditionChart.chart.map((point) => ({
        label: formatOverviewChartLabel(point.timestamp, period),
        value: point.value,
    }));

    return (
        <AuditionCardView
            totalAuditions={totalAuditions}
            bookingRate={bookingRate}
            trendPercentage={trendPercentage}
            displayRange={displayRange}
            chartData={chartData}
        />
    );
};

export type {
    AuditionCardChartDatum,
    AuditionCardProps,
    AuditionCardViewProps,
};
export {
    AuditionCard,
    AuditionCardSkeleton,
    AuditionCardView,
};
