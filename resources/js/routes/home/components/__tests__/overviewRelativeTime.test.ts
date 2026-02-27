import { describe, expect, it } from "vitest";
import {
    getOverviewRelativeTimeParts,
    isOverviewTimestampOverdue,
} from "@/routes/home/components/OverviewScreenTabs/lib/relativeTime";

describe("overview relative time helpers", () => {
    it("returns future duration parts with ceil rounding", () => {
        const now = 1_700_000_000_000;
        const twoDaysAhead = now + (36 * 60 * 60 * 1_000);

        expect(getOverviewRelativeTimeParts(twoDaysAhead, now)).toStrictEqual({
            value: 2,
            unit: "day",
            isPast: false,
        });
    });

    it("returns past duration parts with ceil rounding", () => {
        const now = 1_700_000_000_000;
        const past = now - (61 * 1_000);

        expect(getOverviewRelativeTimeParts(past, now)).toStrictEqual({
            value: 2,
            unit: "minute",
            isPast: true,
        });
    });

    it("returns zero seconds when timestamps are equal", () => {
        const now = 1_700_000_000_000;

        expect(getOverviewRelativeTimeParts(now, now)).toStrictEqual({
            value: 0,
            unit: "second",
            isPast: false,
        });
    });

    it("marks timestamps as overdue only when strictly in the past", () => {
        const now = 1_700_000_000_000;

        expect(isOverviewTimestampOverdue(now - 1, now)).toBe(true);
        expect(isOverviewTimestampOverdue(now, now)).toBe(false);
        expect(isOverviewTimestampOverdue(now + 1, now)).toBe(false);
    });
});
