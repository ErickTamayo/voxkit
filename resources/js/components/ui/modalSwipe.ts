export interface ModalSwipeConfig {
    upwardDragMaxPx: number;
    upwardDragResistance: number;
    dismissDistancePx: number;
    dismissVelocityYThreshold: number;
}

export interface ModalSwipeReleaseInput {
    deltaY: number;
    velocityY: number;
}

export interface ModalSwipeDragInput {
    deltaY: number;
}

export type ModalSwipeReleaseOutcome = "dismiss" | "snap-back";

/**
 * Foundation defaults copied from the existing mobile modal behavior.
 * Note: velocity units depend on the gesture source (RN PanResponder vs Motion).
 * The integration layer may tune `dismissVelocityYThreshold` if Motion reports
 * velocity in different units than React Native.
 */
export const MODAL_SWIPE_DEFAULT_CONFIG: ModalSwipeConfig = {
    upwardDragMaxPx: 36,
    upwardDragResistance: 0.3,
    dismissDistancePx: 90,
    dismissVelocityYThreshold: 1,
};

function clampUpwardDrag(deltaY: number, config: ModalSwipeConfig): number {
    const resisted = deltaY * config.upwardDragResistance;

    return Math.max(-config.upwardDragMaxPx, resisted);
}

/**
 * Converts raw drag delta into the displayed translateY value for the sheet.
 * Positive values (downward drag) are applied directly.
 * Negative values (upward drag) are damped and clamped for an "organic" tug.
 */
export function getModalSwipeDragOffset(
    input: ModalSwipeDragInput,
    config: ModalSwipeConfig = MODAL_SWIPE_DEFAULT_CONFIG,
): number {
    if (input.deltaY < 0) {
        return clampUpwardDrag(input.deltaY, config);
    }

    return input.deltaY;
}

export function shouldDismissFromModalSwipe(
    input: ModalSwipeReleaseInput,
    config: ModalSwipeConfig = MODAL_SWIPE_DEFAULT_CONFIG,
): boolean {
    return (
        input.deltaY >= config.dismissDistancePx
        || input.velocityY >= config.dismissVelocityYThreshold
    );
}

export function getModalSwipeReleaseOutcome(
    input: ModalSwipeReleaseInput,
    config: ModalSwipeConfig = MODAL_SWIPE_DEFAULT_CONFIG,
): ModalSwipeReleaseOutcome {
    return shouldDismissFromModalSwipe(input, config) ? "dismiss" : "snap-back";
}
