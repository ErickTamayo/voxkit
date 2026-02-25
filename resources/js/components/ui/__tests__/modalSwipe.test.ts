import { describe, expect, it } from "vitest";
import {
    MODAL_SWIPE_DEFAULT_CONFIG,
    getModalSwipeDragOffset,
    getModalSwipeReleaseOutcome,
    shouldDismissFromModalSwipe,
} from "@/components/ui/modalSwipe";

describe("modalSwipe", () => {
    it("passes through downward drag offsets", () => {
        expect(getModalSwipeDragOffset({ deltaY: 0 })).toBe(0);
        expect(getModalSwipeDragOffset({ deltaY: 12 })).toBe(12);
        expect(getModalSwipeDragOffset({ deltaY: 180 })).toBe(180);
    });

    it("applies upward drag resistance and clamp", () => {
        expect(getModalSwipeDragOffset({ deltaY: -10 })).toBe(-3);
        expect(getModalSwipeDragOffset({ deltaY: -60 })).toBe(-18);

        // -200 * 0.3 = -60, then clamped to the default upward max (-36)
        expect(getModalSwipeDragOffset({ deltaY: -200 })).toBe(-36);
    });

    it("dismisses on downward distance threshold", () => {
        expect(shouldDismissFromModalSwipe({ deltaY: 89, velocityY: 0.2 })).toBe(false);
        expect(shouldDismissFromModalSwipe({ deltaY: 90, velocityY: 0.2 })).toBe(true);
    });

    it("dismisses on downward velocity threshold", () => {
        expect(shouldDismissFromModalSwipe({ deltaY: 10, velocityY: 0.99 })).toBe(false);
        expect(shouldDismissFromModalSwipe({ deltaY: 10, velocityY: 1 })).toBe(true);
    });

    it("returns snap-back when dismiss thresholds are not met", () => {
        expect(
            getModalSwipeReleaseOutcome({ deltaY: 40, velocityY: 0.4 }),
        ).toBe("snap-back");
    });

    it("returns dismiss when either threshold is met", () => {
        expect(
            getModalSwipeReleaseOutcome({ deltaY: 95, velocityY: 0.1 }),
        ).toBe("dismiss");

        expect(
            getModalSwipeReleaseOutcome({ deltaY: 5, velocityY: 1.2 }),
        ).toBe("dismiss");
    });

    it("supports config overrides for integration tuning", () => {
        const tunedConfig = {
            ...MODAL_SWIPE_DEFAULT_CONFIG,
            upwardDragMaxPx: 20,
            upwardDragResistance: 0.5,
            dismissDistancePx: 120,
            dismissVelocityYThreshold: 300,
        };

        expect(getModalSwipeDragOffset({ deltaY: -80 }, tunedConfig)).toBe(-20);
        expect(shouldDismissFromModalSwipe({ deltaY: 100, velocityY: 200 }, tunedConfig)).toBe(false);
        expect(shouldDismissFromModalSwipe({ deltaY: 100, velocityY: 320 }, tunedConfig)).toBe(true);
    });
});
