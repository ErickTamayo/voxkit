import type { FC } from "react";
import type { Meta, StoryObj } from "@storybook/react-vite";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    OverviewHorizontalBarChart,
} from "@/routes/home/components/OverviewScreenTabs/reports/charts/OverviewHorizontalBarChart";
import { OverviewLineChart } from "@/routes/home/components/OverviewScreenTabs/reports/charts/OverviewLineChart";

const lineDemoData = [
    { label: "Jan 1", value: 3000 },
    { label: "Jan 8", value: 4200 },
    { label: "Jan 15", value: 3600 },
    { label: "Jan 22", value: 5200 },
    { label: "Jan 29", value: 6100 },
    { label: "Feb 5", value: 5400 },
];

const barDemoData = [
    { label: "Direct", value: 41, color: "#7f56d9" },
    { label: "Agent", value: 34, color: "#9e77ed" },
    { label: "Platform", value: 19, color: "#b692f6" },
    { label: "Other", value: 6, color: "#d9d6fe" },
];

const OverviewChartsFoundationDemo: FC = () => {
    return (
        <main className="bg-background min-h-full p-4">
            <div className="mx-auto flex min-h-[calc(100dvh-2rem)] w-full max-w-4xl flex-col gap-4">
                <header className="space-y-2 px-1 pt-2">
                    <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                        Home / Capacitor
                    </p>
                    <h1 className="text-2xl font-semibold">Overview Charts Baseline</h1>
                    <p className="text-sm text-muted-foreground">
                        Foundation smoke preview for react-chartjs-2 wrappers.
                    </p>
                </header>

                <Card>
                    <CardHeader>
                        <CardTitle>Revenue</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <OverviewLineChart
                            data={lineDemoData}
                            tooltipValueFormatter={(value) => `$${Math.round(value)}`}
                            yAxisTickFormatter={(value) => `$${Math.round(value / 1000)}k`}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Top Revenue By Source</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <OverviewHorizontalBarChart
                            data={barDemoData}
                            tooltipValueFormatter={(value) => `${Math.round(value)}%`}
                            xAxisTickFormatter={(value) => `${Math.round(value)}%`}
                        />
                    </CardContent>
                </Card>
            </div>
        </main>
    );
};

const meta = {
    title: "Screens/Home/Capacitor/Overview Charts (Foundation)",
    component: OverviewChartsFoundationDemo,
    parameters: {
        layout: "fullscreen",
    },
} satisfies Meta<typeof OverviewChartsFoundationDemo>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Smoke: Story = {
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
