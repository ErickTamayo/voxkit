import type { FC } from "react";
import { CompactRange } from "@/graphql/types";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

interface CompactRangeSelectorProps {
    selectedRange: CompactRange;
    onSelectRange: (range: CompactRange) => void;
    className?: string;
}

const COMPACT_RANGE_OPTIONS: CompactRange[] = [
    CompactRange.OneWeek,
    CompactRange.FourWeeks,
    CompactRange.OneYear,
    CompactRange.MonthToDate,
    CompactRange.QuarterToDate,
    CompactRange.YearToDate,
    CompactRange.AllTime,
];

const COMPACT_RANGE_LABELS: Record<CompactRange, string> = {
    [CompactRange.OneWeek]: "1W",
    [CompactRange.FourWeeks]: "4W",
    [CompactRange.OneYear]: "1Y",
    [CompactRange.MonthToDate]: "MTD",
    [CompactRange.QuarterToDate]: "QTD",
    [CompactRange.YearToDate]: "YTD",
    [CompactRange.AllTime]: "ALL",
};

const CompactRangeSelector: FC<CompactRangeSelectorProps> = ({
    selectedRange,
    onSelectRange,
    className,
}) => {
    return (
        <section
            className={cn(
                "flex items-center justify-between gap-1",
                className,
            )}
            aria-label="Select report range"
        >
            {COMPACT_RANGE_OPTIONS.map((range) => {
                const isActive = range === selectedRange;

                return (
                    <Button
                        key={range}
                        type="button"
                        variant="ghost"
                        size="sm"
                        aria-pressed={isActive}
                        onClick={() => onSelectRange(range)}
                        className={cn(
                            "h-7 min-w-0 px-2 text-xs font-semibold tracking-wide",
                            isActive
                                ? "bg-primary/10 text-primary hover:bg-primary/15"
                                : "text-muted-foreground hover:text-foreground",
                        )}
                    >
                        {COMPACT_RANGE_LABELS[range]}
                    </Button>
                );
            })}
        </section>
    );
};

export type { CompactRangeSelectorProps };
export { COMPACT_RANGE_LABELS, COMPACT_RANGE_OPTIONS, CompactRangeSelector };
