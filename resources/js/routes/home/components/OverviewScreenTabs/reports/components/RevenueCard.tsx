import type { FC } from "react";
import { useSuspenseQuery } from "@apollo/client/react";
import { TrendingDown, TrendingUp } from "lucide-react";
import { useTranslation } from "react-i18next";
import { CompactRange } from "@/graphql/types";
import {
    formatOverviewCompactCurrencyFromCents,
    formatOverviewCurrencyFromCents,
    formatOverviewPercentage,
} from "@/routes/home/components/OverviewScreenTabs/lib/formatters";
import {
    OverviewRevenueChartDocument,
} from "@/routes/home/components/OverviewScreenTabs/reports/RevenueChart.graphql.ts";
import { OverviewLineChart } from "@/routes/home/components/OverviewScreenTabs/reports/charts/OverviewLineChart";
import {
    formatOverviewChartDisplayRange,
    formatOverviewChartLabel,
} from "@/routes/home/components/OverviewScreenTabs/reports/reportTransformers";

interface RevenueCardProps {
    period: CompactRange;
}

interface RevenueCardChartDatum {
    label: string;
    value: number;
}

interface RevenueCardViewProps {
    baseCurrency: string;
    chartData: RevenueCardChartDatum[];
    displayRange: string;
    pendingCents: number;
    totalCents: number;
    trendPercentage: number;
}

const RevenueCardView: FC<RevenueCardViewProps> = ({
    baseCurrency,
    chartData,
    displayRange,
    pendingCents,
    totalCents,
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
                            {t("Revenue")}
                        </p>
                        <div className="flex items-center gap-2">
                            <p className="text-2xl font-semibold text-foreground">
                                {formatOverviewCompactCurrencyFromCents(totalCents, baseCurrency)}
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
                            {t("Pending")}
                        </p>
                        <p className="text-2xl font-medium text-muted-foreground">
                            {formatOverviewCompactCurrencyFromCents(pendingCents, baseCurrency)}
                        </p>
                    </div>
                </div>

                <p className="text-xs text-muted-foreground">{displayRange}</p>
            </header>

            {chartData.length > 0 ? (
                <OverviewLineChart
                    data={chartData}
                    tooltipValueFormatter={(value) => {
                        return formatOverviewCurrencyFromCents(
                            Math.round(value),
                            baseCurrency,
                        );
                    }}
                    yAxisTickFormatter={(value) => {
                        return formatOverviewCompactCurrencyFromCents(
                            Math.round(value),
                            baseCurrency,
                        );
                    }}
                />
            ) : (
                <div className="rounded-md border border-dashed border-border/80 p-6">
                    <p className="text-sm text-muted-foreground">
                        {t("No revenue points in this period.")}
                    </p>
                </div>
            )}
        </section>
    );
};

const RevenueCardSkeleton: FC = () => {
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

const RevenueCard: FC<RevenueCardProps> = ({
    period,
}) => {
    const { data } = useSuspenseQuery(OverviewRevenueChartDocument, {
        variables: {
            period,
        },
    });
    const baseCurrency = data.revenueMetrics.baseCurrency;
    const totalCents = data.revenueMetrics.metrics.current.total.amount_cents;
    const pendingCents = data.revenueMetrics.metrics.in_flight.total.amount_cents;
    const trendPercentage = data.revenueMetrics.metrics.current.trend_percentage;
    const displayRange = formatOverviewChartDisplayRange({
        period,
        periodStart: data.revenueMetrics.period.start,
        periodEnd: data.revenueMetrics.period.end,
    });
    const chartData = data.revenueChart.chart.map((point) => ({
        label: formatOverviewChartLabel(point.timestamp, period),
        value: point.value,
    }));

    return (
        <RevenueCardView
            baseCurrency={baseCurrency}
            totalCents={totalCents}
            pendingCents={pendingCents}
            trendPercentage={trendPercentage}
            displayRange={displayRange}
            chartData={chartData}
        />
    );
};

export type {
    RevenueCardChartDatum,
    RevenueCardProps,
    RevenueCardViewProps,
};
export {
    RevenueCard,
    RevenueCardSkeleton,
    RevenueCardView,
};
