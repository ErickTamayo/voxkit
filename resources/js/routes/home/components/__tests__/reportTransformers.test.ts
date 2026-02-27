import { describe, expect, it } from "vitest";
import { CompactRange, ProjectCategory } from "@/graphql/types";
import {
    formatOverviewChartDisplayRange,
    formatOverviewChartLabel,
    getOverviewBarMaxValue,
    getOverviewProjectCategoryLabel,
    mixOverviewColorWithWhite,
    toOverviewLineData,
    toOverviewRevenueByCategoryBars,
    toOverviewRevenueBySourceBars,
} from "@/routes/home/components/OverviewScreenTabs/reports/reportTransformers";

describe("reportTransformers", () => {
    it("formats chart labels in UTC based on range granularity", () => {
        const timestamp = Date.UTC(2026, 0, 2, 0, 0, 0);

        expect(
            formatOverviewChartLabel(timestamp, CompactRange.OneWeek),
        ).toBe("Jan 2");
        expect(
            formatOverviewChartLabel(timestamp, CompactRange.YearToDate),
        ).toBe("Jan");
        expect(
            formatOverviewChartLabel(timestamp, CompactRange.YearToDate, { includeYear: true }),
        ).toBe("Jan 2026");
    });

    it("formats chart display ranges", () => {
        const start = Date.UTC(2026, 0, 1, 0, 0, 0);
        const end = Date.UTC(2026, 0, 7, 0, 0, 0);

        expect(formatOverviewChartDisplayRange({
            period: CompactRange.OneWeek,
            periodStart: start,
            periodEnd: end,
        })).toBe("Jan 1 - Jan 7");
    });

    it("creates source bars sorted by percentage and scaled to 100 when source totals are fractions", () => {
        const data = toOverviewRevenueBySourceBars({
            baseColor: "#7f56d9",
            entries: [
                { source_name: "Direct", percentage_of_total: 0.3 },
                { source_name: "Agent", percentage_of_total: 0.5 },
                { source_name: "Platform", percentage_of_total: 0 },
            ],
        });

        expect(data).toHaveLength(2);
        expect(data[0].label).toBe("Agent (50%)");
        expect(data[0].value).toBe(50);
        expect(data[0].color).toBe("#7f56d9");
        expect(data[1].label).toBe("Direct (30%)");
        expect(data[1].value).toBe(30);
    });

    it("creates category bars without scaling when totals are already percentage values", () => {
        const data = toOverviewRevenueByCategoryBars({
            baseColor: "#7f56d9",
            entries: [
                { category: ProjectCategory.Unknown, percentage_of_total: 10 },
                { category: ProjectCategory.Commercial, percentage_of_total: 65.2 },
            ],
        });

        expect(data).toHaveLength(2);
        expect(data[0].label).toBe("Commercial (65.2%)");
        expect(data[0].value).toBe(65.2);
        expect(data[1].label).toBe("Unknown (10%)");
    });

    it("maps line data and max values for charts", () => {
        const lineData = toOverviewLineData([
            { timestamp: 10, value: 4 },
            { timestamp: 20, value: 9 },
        ]);

        expect(lineData).toStrictEqual([
            { x: 10, y: 4 },
            { x: 20, y: 9 },
        ]);
        expect(getOverviewBarMaxValue([
            { value: 0 },
            { value: 27 },
            { value: 3 },
        ])).toBe(27);
    });

    it("exposes category labels and color tint mixing", () => {
        expect(getOverviewProjectCategoryLabel(ProjectCategory.Elearning)).toBe("eLearning");
        expect(mixOverviewColorWithWhite("#7f56d9", 0)).toBe("#7f56d9");
        expect(mixOverviewColorWithWhite("#7f56d9", 1)).toBe("#ffffff");
    });
});
