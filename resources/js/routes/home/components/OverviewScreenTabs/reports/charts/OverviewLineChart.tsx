import type { FC } from "react";
import type { ChartData, ChartOptions, TooltipItem } from "chart.js";
import { useReducedMotion } from "motion/react";
import { Line } from "react-chartjs-2";
import { cn } from "@/lib/utils";
import { ensureOverviewChartRegistry } from "@/routes/home/components/OverviewScreenTabs/reports/charts/chartRegistry";

interface OverviewLineChartDatum {
    label: string;
    value: number;
}

interface OverviewLineChartProps {
    data: OverviewLineChartDatum[];
    className?: string;
    height?: number;
    lineColor?: string;
    tooltipTitleFormatter?: (label: string) => string;
    tooltipValueFormatter?: (value: number) => string;
    yAxisTickFormatter?: (value: number) => string;
}

const DEFAULT_LINE_COLOR = "#7f56d9";

const OverviewLineChart: FC<OverviewLineChartProps> = ({
    data,
    className,
    height = 240,
    lineColor = DEFAULT_LINE_COLOR,
    tooltipTitleFormatter,
    tooltipValueFormatter,
    yAxisTickFormatter,
}) => {
    ensureOverviewChartRegistry();

    const prefersReducedMotion = useReducedMotion();
    const chartData: ChartData<"line", { x: number; y: number }[]> = {
        datasets: [
            {
                data: data.map((entry, index) => ({
                    x: index,
                    y: entry.value,
                })),
                borderColor: lineColor,
                backgroundColor: lineColor,
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 3,
                pointHitRadius: 12,
                tension: 0.35,
                fill: false,
                parsing: false,
                normalized: true,
            },
        ],
    };
    const chartOptions: ChartOptions<"line"> = {
        responsive: true,
        maintainAspectRatio: false,
        animation: prefersReducedMotion
            ? false
            : {
                  duration: 220,
              },
        interaction: {
            mode: "nearest",
            intersect: false,
        },
        plugins: {
            legend: {
                display: false,
            },
            decimation: {
                enabled: data.length > 40,
                algorithm: "lttb",
                samples: 24,
            },
            tooltip: {
                callbacks: {
                    title: (items: TooltipItem<"line">[]) => {
                        const index = Math.round(items[0]?.parsed.x ?? 0);
                        const label = data[index]?.label ?? "";

                        if (tooltipTitleFormatter !== undefined) {
                            return tooltipTitleFormatter(label);
                        }

                        return label;
                    },
                    label: (item: TooltipItem<"line">) => {
                        const value = item.parsed.y;

                        if (value === null) {
                            return "";
                        }

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
                type: "linear",
                min: 0,
                max: Math.max(0, data.length - 1),
                grid: {
                    display: false,
                },
                ticks: {
                    autoSkip: true,
                    maxTicksLimit: 7,
                    stepSize: 1,
                    callback: (value) => {
                        const index = Number(value);

                        if (!Number.isFinite(index)) {
                            return "";
                        }

                        return data[Math.round(index)]?.label ?? "";
                    },
                },
            },
            y: {
                beginAtZero: true,
                ticks: {
                    callback: (value) => {
                        const numericValue = Number(value);

                        if (!Number.isFinite(numericValue)) {
                            return "";
                        }

                        if (yAxisTickFormatter !== undefined) {
                            return yAxisTickFormatter(numericValue);
                        }

                        return String(numericValue);
                    },
                },
            },
        },
    };

    return (
        <div className={cn("w-full", className)} style={{ height }}>
            <Line data={chartData} options={chartOptions} />
        </div>
    );
};

export type { OverviewLineChartDatum, OverviewLineChartProps };
export { OverviewLineChart };
