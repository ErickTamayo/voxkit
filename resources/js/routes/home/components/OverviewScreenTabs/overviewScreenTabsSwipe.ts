export interface OverviewScreenTabsSwipeConfig {
    changeDistancePx: number;
    changeVelocityXPxPerSecond: number;
}

export interface OverviewScreenTabsSwipeInput {
    deltaX: number;
    velocityX: number;
}

export type OverviewScreenTabsSwipeOutcome = "next" | "prev" | "stay";

export const OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG: OverviewScreenTabsSwipeConfig = {
    changeDistancePx: 72,
    changeVelocityXPxPerSecond: 520,
};

function shouldMoveToPreviousTab(
    input: OverviewScreenTabsSwipeInput,
    config: OverviewScreenTabsSwipeConfig,
): boolean {
    return (
        input.deltaX >= config.changeDistancePx
        || input.velocityX >= config.changeVelocityXPxPerSecond
    );
}

function shouldMoveToNextTab(
    input: OverviewScreenTabsSwipeInput,
    config: OverviewScreenTabsSwipeConfig,
): boolean {
    return (
        input.deltaX <= -config.changeDistancePx
        || input.velocityX <= -config.changeVelocityXPxPerSecond
    );
}

export function getOverviewScreenTabsSwipeOutcome(
    input: OverviewScreenTabsSwipeInput,
    config: OverviewScreenTabsSwipeConfig = OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG,
): OverviewScreenTabsSwipeOutcome {
    if (shouldMoveToPreviousTab(input, config)) {
        return "prev";
    }

    if (shouldMoveToNextTab(input, config)) {
        return "next";
    }

    return "stay";
}
