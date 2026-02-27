import { useState, type FC, type ReactNode } from "react";
import type { Meta, StoryObj } from "@storybook/react-vite";
import { CompactRange } from "@/graphql/types";
import { CompactRangeSelector } from "@/routes/home/components/OverviewScreenTabs/reports/components/CompactRangeSelector";
import {
    AuditionCardSkeleton,
    AuditionCardView,
} from "@/routes/home/components/OverviewScreenTabs/reports/components/AuditionCard";
import {
    RevenueByCategoryCardSkeleton,
    RevenueByCategoryCardView,
} from "@/routes/home/components/OverviewScreenTabs/reports/components/RevenueByCategoryCard";
import {
    RevenueBySourceCardSkeleton,
    RevenueBySourceCardView,
} from "@/routes/home/components/OverviewScreenTabs/reports/components/RevenueBySourceCard";
import {
    RevenueCardSkeleton,
    RevenueCardView,
} from "@/routes/home/components/OverviewScreenTabs/reports/components/RevenueCard";

const reportsLineData = [
    { label: "Jan 1", value: 390_000 },
    { label: "Jan 8", value: 540_000 },
    { label: "Jan 15", value: 475_000 },
    { label: "Jan 22", value: 628_000 },
    { label: "Jan 29", value: 592_000 },
];

const auditionsLineData = [
    { label: "Jan 1", value: 8 },
    { label: "Jan 8", value: 13 },
    { label: "Jan 15", value: 11 },
    { label: "Jan 22", value: 16 },
    { label: "Jan 29", value: 14 },
];

const sourceBars = [
    { label: "Direct (44%)", value: 44, color: "#7f56d9" },
    { label: "Agent (31%)", value: 31, color: "#9e77ed" },
    { label: "Platform (18%)", value: 18, color: "#b692f6" },
    { label: "Other (7%)", value: 7, color: "#d9d6fe" },
];

const categoryBars = [
    { label: "Commercial (41%)", value: 41, color: "#7f56d9" },
    { label: "eLearning (27%)", value: 27, color: "#9e77ed" },
    { label: "Trailer (21%)", value: 21, color: "#b692f6" },
    { label: "Animation (11%)", value: 11, color: "#d9d6fe" },
];

interface ReportsCardsStoryFrameProps {
    children: ReactNode;
}

const ReportsCardsStoryFrame: FC<ReportsCardsStoryFrameProps> = ({
    children,
}) => {
    return (
        <main className="bg-background min-h-full p-4">
            <div className="mx-auto flex min-h-[calc(100dvh-2rem)] w-full max-w-4xl flex-col gap-4">
                <header className="space-y-2 px-1 pt-2">
                    <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                        Home / Capacitor
                    </p>
                    <h1 className="text-2xl font-semibold">Overview Reports Cards</h1>
                    <p className="text-sm text-muted-foreground">
                        Leaf reports sections for visual parity review.
                    </p>
                </header>

                {children}
            </div>
        </main>
    );
};

const ReportsCardsDemo: FC = () => {
    const [range, setRange] = useState<CompactRange>(CompactRange.OneWeek);

    return (
        <ReportsCardsStoryFrame>
            <section className="-mx-4 border-y border-border/70 bg-background">
                <div className="border-b border-border/70 px-4 py-2">
                    <CompactRangeSelector selectedRange={range} onSelectRange={setRange} />
                </div>

                <div className="divide-y divide-border/70">
                    <RevenueCardView
                        baseCurrency="USD"
                        totalCents={2_624_500}
                        pendingCents={761_200}
                        trendPercentage={18.2}
                        displayRange="Jan 1 - Jan 29"
                        chartData={reportsLineData}
                    />
                    <AuditionCardView
                        totalAuditions={52}
                        bookingRate={21.7}
                        trendPercentage={11.4}
                        displayRange="Jan 1 - Jan 29"
                        chartData={auditionsLineData}
                    />
                    <RevenueBySourceCardView
                        displayRange="Jan 1 - Jan 29"
                        bars={sourceBars}
                    />
                    <RevenueByCategoryCardView
                        displayRange="Jan 1 - Jan 29"
                        bars={categoryBars}
                    />
                </div>
            </section>
        </ReportsCardsStoryFrame>
    );
};

const ReportsCardsEmptyDemo: FC = () => {
    return (
        <ReportsCardsStoryFrame>
            <section className="-mx-4 border-y border-border/70 bg-background">
                <div className="border-b border-border/70 px-4 py-2">
                    <CompactRangeSelector
                        selectedRange={CompactRange.OneWeek}
                        onSelectRange={() => {}}
                    />
                </div>

                <div className="divide-y divide-border/70">
                    <RevenueCardView
                        baseCurrency="USD"
                        totalCents={0}
                        pendingCents={0}
                        trendPercentage={0}
                        displayRange="Jan 1 - Jan 29"
                        chartData={[]}
                    />
                    <AuditionCardView
                        totalAuditions={0}
                        bookingRate={0}
                        trendPercentage={0}
                        displayRange="Jan 1 - Jan 29"
                        chartData={[]}
                    />
                    <RevenueBySourceCardView
                        displayRange="Jan 1 - Jan 29"
                        bars={[]}
                    />
                    <RevenueByCategoryCardView
                        displayRange="Jan 1 - Jan 29"
                        bars={[]}
                    />
                </div>
            </section>
        </ReportsCardsStoryFrame>
    );
};

const RevenueCardDemo: FC = () => {
    return (
        <ReportsCardsStoryFrame>
            <section className="-mx-4 border-y border-border/70 bg-background">
                <div className="border-b border-border/70 px-4 py-2">
                    <CompactRangeSelector
                        selectedRange={CompactRange.OneWeek}
                        onSelectRange={() => {}}
                    />
                </div>
                <RevenueCardView
                    baseCurrency="USD"
                    totalCents={2_624_500}
                    pendingCents={761_200}
                    trendPercentage={18.2}
                    displayRange="Jan 1 - Jan 29"
                    chartData={reportsLineData}
                />
            </section>
        </ReportsCardsStoryFrame>
    );
};

const AuditionCardDemo: FC = () => {
    return (
        <ReportsCardsStoryFrame>
            <section className="-mx-4 border-y border-border/70 bg-background">
                <div className="border-b border-border/70 px-4 py-2">
                    <CompactRangeSelector
                        selectedRange={CompactRange.OneWeek}
                        onSelectRange={() => {}}
                    />
                </div>
                <AuditionCardView
                    totalAuditions={52}
                    bookingRate={21.7}
                    trendPercentage={11.4}
                    displayRange="Jan 1 - Jan 29"
                    chartData={auditionsLineData}
                />
            </section>
        </ReportsCardsStoryFrame>
    );
};

const RevenueBySourceCardDemo: FC = () => {
    return (
        <ReportsCardsStoryFrame>
            <section className="-mx-4 border-y border-border/70 bg-background">
                <div className="border-b border-border/70 px-4 py-2">
                    <CompactRangeSelector
                        selectedRange={CompactRange.OneWeek}
                        onSelectRange={() => {}}
                    />
                </div>
                <RevenueBySourceCardView
                    displayRange="Jan 1 - Jan 29"
                    bars={sourceBars}
                />
            </section>
        </ReportsCardsStoryFrame>
    );
};

const RevenueByCategoryCardDemo: FC = () => {
    return (
        <ReportsCardsStoryFrame>
            <section className="-mx-4 border-y border-border/70 bg-background">
                <div className="border-b border-border/70 px-4 py-2">
                    <CompactRangeSelector
                        selectedRange={CompactRange.OneWeek}
                        onSelectRange={() => {}}
                    />
                </div>
                <RevenueByCategoryCardView
                    displayRange="Jan 1 - Jan 29"
                    bars={categoryBars}
                />
            </section>
        </ReportsCardsStoryFrame>
    );
};

const ReportsCardsSkeletonDemo: FC = () => {
    return (
        <ReportsCardsStoryFrame>
            <section className="-mx-4 border-y border-border/70 bg-background">
                <div className="border-b border-border/70 px-4 py-2">
                    <CompactRangeSelector
                        selectedRange={CompactRange.OneWeek}
                        onSelectRange={() => {}}
                    />
                </div>

                <div className="divide-y divide-border/70">
                    <RevenueCardSkeleton />
                    <AuditionCardSkeleton />
                    <RevenueBySourceCardSkeleton />
                    <RevenueByCategoryCardSkeleton />
                </div>
            </section>
        </ReportsCardsStoryFrame>
    );
};

const meta = {
    title: "Screens/Home/Capacitor/Overview Reports Cards",
    component: ReportsCardsDemo,
    parameters: {
        layout: "fullscreen",
    },
} satisfies Meta<typeof ReportsCardsDemo>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Base: Story = {
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

export const EmptyStates: Story = {
    render: () => <ReportsCardsEmptyDemo />,
    globals: Base.globals,
    parameters: Base.parameters,
};

export const RevenueCardOnly: Story = {
    render: () => <RevenueCardDemo />,
    globals: Base.globals,
    parameters: Base.parameters,
};

export const AuditionCardOnly: Story = {
    render: () => <AuditionCardDemo />,
    globals: Base.globals,
    parameters: Base.parameters,
};

export const RevenueBySourceCardOnly: Story = {
    render: () => <RevenueBySourceCardDemo />,
    globals: Base.globals,
    parameters: Base.parameters,
};

export const RevenueByCategoryCardOnly: Story = {
    render: () => <RevenueByCategoryCardDemo />,
    globals: Base.globals,
    parameters: Base.parameters,
};

export const SkeletonStates: Story = {
    render: () => <ReportsCardsSkeletonDemo />,
    globals: Base.globals,
    parameters: Base.parameters,
};
