export interface OverviewScreenTabsSwipeConfig {
    changeDistancePx: number;
    changeVelocityXPxPerSecond: number;
    horizontalDominanceRatio: number;
    minVelocityDistancePx: number;
}

export interface OverviewScreenTabsSwipeInput {
    deltaX: number;
    deltaY?: number;
    velocityX: number;
}

export type OverviewScreenTabsSwipeOutcome = "next" | "prev" | "stay";

export const OVERVIEW_SCREEN_TABS_SWIPE_DEFAULT_CONFIG: OverviewScreenTabsSwipeConfig = {
    changeDistancePx: 72,
    changeVelocityXPxPerSecond: 520,
    horizontalDominanceRatio: 1.15,
    minVelocityDistancePx: 12,
};

function isHorizontalSwipeIntent(
    input: OverviewScreenTabsSwipeInput,
    config: OverviewScreenTabsSwipeConfig,
): boolean {
    if (input.deltaY === undefined) {
        return true;
    }

    const absDeltaX = Math.abs(input.deltaX);
    const absDeltaY = Math.abs(input.deltaY);

    return absDeltaX >= absDeltaY * config.horizontalDominanceRatio;
}

function shouldMoveToPreviousTab(
    input: OverviewScreenTabsSwipeInput,
    config: OverviewScreenTabsSwipeConfig,
): boolean {
    const meetsDistanceThreshold = input.deltaX >= config.changeDistancePx;
    const meetsVelocityThreshold = (
        input.deltaX >= config.minVelocityDistancePx
        && input.velocityX >= config.changeVelocityXPxPerSecond
    );

    return (
        isHorizontalSwipeIntent(input, config)
        && (meetsDistanceThreshold || meetsVelocityThreshold)
    );
}

function shouldMoveToNextTab(
    input: OverviewScreenTabsSwipeInput,
    config: OverviewScreenTabsSwipeConfig,
): boolean {
    const meetsDistanceThreshold = input.deltaX <= -config.changeDistancePx;
    const meetsVelocityThreshold = (
        input.deltaX <= -config.minVelocityDistancePx
        && input.velocityX <= -config.changeVelocityXPxPerSecond
    );

    return (
        isHorizontalSwipeIntent(input, config)
        && (meetsDistanceThreshold || meetsVelocityThreshold)
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
