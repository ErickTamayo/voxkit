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
                deltaY: 0,
                velocityX: 0,
            }),
        ).toBe("prev");

        expect(
            getOverviewScreenTabsSwipeOutcome({
                deltaX: OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.minVelocityDistancePx,
                deltaY: 0,
                velocityX: OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.changeVelocityXPxPerSecond,
            }),
        ).toBe("prev");
    });

    it("moves to next tab on leftward distance or velocity threshold", () => {
        expect(
            getOverviewScreenTabsSwipeOutcome({
                deltaX: -OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.changeDistancePx,
                deltaY: 0,
                velocityX: 0,
            }),
        ).toBe("next");

        expect(
            getOverviewScreenTabsSwipeOutcome({
                deltaX: -OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.minVelocityDistancePx,
                deltaY: 0,
                velocityX: -OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.changeVelocityXPxPerSecond,
            }),
        ).toBe("next");
    });

    it("does not change tab when vertical swipe dominates gesture intent", () => {
        expect(
            getOverviewScreenTabsSwipeOutcome({
                deltaX: OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.changeDistancePx,
                deltaY: 120,
                velocityX: OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.changeVelocityXPxPerSecond + 30,
            }),
        ).toBe("stay");
        expect(
            getOverviewScreenTabsSwipeOutcome({
                deltaX: -OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.changeDistancePx,
                deltaY: -120,
                velocityX: -OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.changeVelocityXPxPerSecond - 30,
            }),
        ).toBe("stay");
    });

    it("does not change tab on tiny horizontal jitter even with high velocity", () => {
        expect(
            getOverviewScreenTabsSwipeOutcome({
                deltaX: OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.minVelocityDistancePx - 1,
                deltaY: 0,
                velocityX: OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.changeVelocityXPxPerSecond + 30,
            }),
        ).toBe("stay");
        expect(
            getOverviewScreenTabsSwipeOutcome({
                deltaX: -(OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.minVelocityDistancePx - 1),
                deltaY: 0,
                velocityX: -OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG.changeVelocityXPxPerSecond - 30,
            }),
        ).toBe("stay");
    });

    it("supports config overrides for integration tuning", () => {
        const config = {
            ...OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG,
            changeDistancePx: 120,
            changeVelocityXPxPerSecond: 900,
            minVelocityDistancePx: 24,
        };

        expect(
            getOverviewScreenTabsSwipeOutcome({ deltaX: 100, velocityX: 500 }, config),
        ).toBe("stay");
        expect(
            getOverviewScreenTabsSwipeOutcome({ deltaX: 121, velocityX: 0 }, config),
        ).toBe("prev");
        expect(
            getOverviewScreenTabsSwipeOutcome({ deltaX: -25, velocityX: -950 }, config),
        ).toBe("next");
    });
});
