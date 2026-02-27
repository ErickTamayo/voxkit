import { Suspense, type FC } from "react";
import { OverviewPeriodProvider, useOverviewPeriod } from "@/routes/home/components/OverviewScreenTabs/state/OverviewPeriodContext";
import { CompactRangeSelector } from "@/routes/home/components/OverviewScreenTabs/reports/components/CompactRangeSelector";
import {
    AuditionCard,
    AuditionCardSkeleton,
} from "@/routes/home/components/OverviewScreenTabs/reports/components/AuditionCard";
import {
    RevenueByCategoryCard,
    RevenueByCategoryCardSkeleton,
} from "@/routes/home/components/OverviewScreenTabs/reports/components/RevenueByCategoryCard";
import {
    RevenueBySourceCard,
    RevenueBySourceCardSkeleton,
} from "@/routes/home/components/OverviewScreenTabs/reports/components/RevenueBySourceCard";
import {
    RevenueCard,
    RevenueCardSkeleton,
} from "@/routes/home/components/OverviewScreenTabs/reports/components/RevenueCard";

const ReportsScreenContent: FC = () => {
    const { selectedRange, setSelectedRange } = useOverviewPeriod();

    return (
        <div className="-mx-4 h-full overflow-y-auto border-y border-border/70 bg-background">
            <div className="sticky top-0 z-10 border-b border-border/70 bg-background px-4 py-2">
                <CompactRangeSelector
                    selectedRange={selectedRange}
                    onSelectRange={setSelectedRange}
                />
            </div>

            <div className="divide-y divide-border/70">
                <Suspense fallback={<RevenueCardSkeleton />}>
                    <RevenueCard period={selectedRange} />
                </Suspense>
                <Suspense fallback={<AuditionCardSkeleton />}>
                    <AuditionCard period={selectedRange} />
                </Suspense>
                <Suspense fallback={<RevenueBySourceCardSkeleton />}>
                    <RevenueBySourceCard period={selectedRange} />
                </Suspense>
                <Suspense fallback={<RevenueByCategoryCardSkeleton />}>
                    <RevenueByCategoryCard period={selectedRange} />
                </Suspense>
            </div>
        </div>
    );
};

const ReportsScreen: FC = () => {
    return (
        <OverviewPeriodProvider>
            <ReportsScreenContent />
        </OverviewPeriodProvider>
    );
};

export { ReportsScreen };
