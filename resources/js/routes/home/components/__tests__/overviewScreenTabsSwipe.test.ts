import { describe, expect, it } from "vitest";
import {
    OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG,
    getOverviewScreenTabsSwipeOutcome,
} from "@/routes/home/components/OverviewScreenTabs/overviewScreenTabsSwipe";

describe("overviewScreenTabsSwipe", () => {
    it("stays when horizontal swipe thresholds are not met", () => {
        expect(
            getOverviewScreenTabsSwipeOutcome({ deltaX: 30, velocityX: 120 }),
        ).toBe("stay");
        expect(
            getOverviewScreenTabsSwipeOutcome({ deltaX: -50, velocityX: -300 }),
        ).toBe("stay");
    });

    it("moves to previous tab on rightward distance or velocity threshold", () => {
        expect(
            getOverviewScreenTabsSwipeOutcome({
                deltaX: OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.changeDistancePx,
                velocityX: 0,
            }),
        ).toBe("prev");

        expect(
            getOverviewScreenTabsSwipeOutcome({
                deltaX: 4,
                velocityX: OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.changeVelocityXPxPerSecond,
            }),
        ).toBe("prev");
    });

    it("moves to next tab on leftward distance or velocity threshold", () => {
        expect(
            getOverviewScreenTabsSwipeOutcome({
                deltaX: -OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.changeDistancePx,
                velocityX: 0,
            }),
        ).toBe("next");

        expect(
            getOverviewScreenTabsSwipeOutcome({
                deltaX: -4,
                velocityX: -OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.changeVelocityXPxPerSecond,
            }),
        ).toBe("next");
    });

    it("supports config overrides for integration tuning", () => {
        const config = {
            ...OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG,
            changeDistancePx: 120,
            changeVelocityXPxPerSecond: 900,
        };

        expect(
            getOverviewScreenTabsSwipeOutcome({ deltaX: 100, velocityX: 500 }, config),
        ).toBe("stay");
        expect(
            getOverviewScreenTabsSwipeOutcome({ deltaX: 121, velocityX: 0 }, config),
        ).toBe("prev");
        expect(
            getOverviewScreenTabsSwipeOutcome({ deltaX: 0, velocityX: -950 }, config),
        ).toBe("next");
    });
});
