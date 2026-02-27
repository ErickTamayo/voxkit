import type { FC } from "react";
import type { ChartData, ChartOptions, TooltipItem } from "chart.js";
import { useReducedMotion } from "motion/react";
import { Bar } from "react-chartjs-2";
import { cn } from "@/lib/utils";
import { ensureOverviewChartRegistry } from "@/routes/home/components/OverviewScreenTabs/reports/charts/chartRegistry";

interface OverviewHorizontalBarChartDatum {
    color?: string;
    label: string;
    value: number;
}

interface OverviewHorizontalBarChartProps {
    data: OverviewHorizontalBarChartDatum[];
    className?: string;
    height?: number;
    tooltipValueFormatter?: (value: number) => string;
    xAxisTickFormatter?: (value: number) => string;
}

const DEFAULT_BAR_COLOR = "#7f56d9";

const OverviewHorizontalBarChart: FC<OverviewHorizontalBarChartProps> = ({
    data,
    className,
    height = 240,
    tooltipValueFormatter,
    xAxisTickFormatter,
}) => {
    ensureOverviewChartRegistry();

    const prefersReducedMotion = useReducedMotion();
    const chartData: ChartData<"bar", number[], string> = {
        labels: data.map((entry) => entry.label),
        datasets: [
            {
                data: data.map((entry) => entry.value),
                backgroundColor: data.map((entry) => entry.color ?? DEFAULT_BAR_COLOR),
                borderColor: data.map((entry) => entry.color ?? DEFAULT_BAR_COLOR),
                borderWidth: 1,
                borderRadius: 6,
                borderSkipped: false,
                barThickness: 12,
                maxBarThickness: 14,
                normalized: true,
            },
        ],
    };
    const chartOptions: ChartOptions<"bar"> = {
        indexAxis: "y",
        responsive: true,
        maintainAspectRatio: false,
        animation: prefersReducedMotion
            ? false
            : {
                  duration: 220,
              },
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                callbacks: {
                    label: (item: TooltipItem<"bar">) => {
                        const value = Number(item.parsed.x);

                        if (tooltipValueFormatter !== undefined) {
                            return tooltipValueFormatter(value);
                        }

                        return String(value);
                    },
                },
            },
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    callback: (value) => {
                        const numericValue = Number(value);

                        if (!Number.isFinite(numericValue)) {
                            return "";
                        }

                        if (xAxisTickFormatter !== undefined) {
                            return xAxisTickFormatter(numericValue);
                        }

                        return String(Math.round(numericValue));
                    },
                },
            },
            y: {
                ticks: {
                    autoSkip: false,
                },
                grid: {
                    display: false,
                },
            },
        },
    };

    return (
        <div className={cn("w-full", className)} style={{ height }}>
            <Bar data={chartData} options={chartOptions} />
        </div>
    );
};

export type {
    OverviewHorizontalBarChartDatum,
    OverviewHorizontalBarChartProps,
};
export { OverviewHorizontalBarChart };
