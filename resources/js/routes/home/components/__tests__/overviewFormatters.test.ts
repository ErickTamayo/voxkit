import { describe, expect, it } from "vitest";
import {
    formatOverviewCompactCurrencyFromCents,
    formatOverviewCurrencyFromCents,
    formatOverviewInteger,
    formatOverviewPercentage,
} from "@/routes/home/components/OverviewScreenTabs/lib/formatters";

describe("overview formatter helpers", () => {
    it("formats percentages like the mobile overview implementation", () => {
        expect(formatOverviewPercentage(5)).toBe("5%");
        expect(formatOverviewPercentage(5.004)).toBe("5%");
        expect(formatOverviewPercentage(5.26)).toBe("5.3%");
    });

    it("formats integers", () => {
        expect(formatOverviewInteger(12_345, "en-US")).toBe("12,345");
    });

    it("formats currency values from cents", () => {
        expect(formatOverviewCurrencyFromCents(12_345, "USD", "en-US")).toBe("$123.45");
    });

    it("formats compact currency and strips trailing .0 precision", () => {
        const value = formatOverviewCompactCurrencyFromCents(12_300_000, "USD", "en-US");

        expect(value).toBe("$123K");
        expect(value.includes(".0")).toBe(false);
    });
});
