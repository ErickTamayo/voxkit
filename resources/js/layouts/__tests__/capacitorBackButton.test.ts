import { beforeEach, describe, expect, it, vi } from "vitest";
import {
    registerCapacitorAndroidBackHandler,
    shouldRegisterCapacitorAndroidBackHandler,
    type BackButtonEvent,
    type CapacitorAppPlugin,
    type CapacitorRuntime,
    type PluginListenerHandle,
} from "@/layouts/capacitorBackButton";

function createCapacitorRuntime(overrides?: Partial<CapacitorRuntime>): CapacitorRuntime {
    return {
        isNativePlatform: () => true,
        getPlatform: () => "android",
        ...overrides,
    };
}

describe("capacitorBackButton", () => {
    beforeEach(() => {
        vi.restoreAllMocks();
    });

    it("registers only on native android", () => {
        expect(
            shouldRegisterCapacitorAndroidBackHandler(
                createCapacitorRuntime({
                    isNativePlatform: () => false,
                }),
            ),
        ).toBe(false);

        expect(
            shouldRegisterCapacitorAndroidBackHandler(
                createCapacitorRuntime({
                    getPlatform: () => "ios",
                }),
            ),
        ).toBe(false);

        expect(
            shouldRegisterCapacitorAndroidBackHandler(createCapacitorRuntime()),
        ).toBe(true);
    });

    it("does not register a listener when not native android", () => {
        const addListener = vi.fn();
        const appPlugin: CapacitorAppPlugin = {
            addListener,
        };

        const cleanup = registerCapacitorAndroidBackHandler({
            appPlugin,
            capacitor: createCapacitorRuntime({
                isNativePlatform: () => false,
            }),
        });

        expect(addListener).not.toHaveBeenCalled();
        expect(cleanup).toBeTypeOf("function");
    });

    it("navigates back only when the native event can go back", async () => {
        const remove = vi.fn<PluginListenerHandle["remove"]>().mockResolvedValue(undefined);
        let backButtonListener: ((event: BackButtonEvent) => void) | null = null;

        const appPlugin: CapacitorAppPlugin = {
            addListener: vi.fn((eventName, listener) => {
                expect(eventName).toBe("backButton");
                backButtonListener = listener;

                return {
                    remove,
                };
            }),
        };
        const onNavigateBack = vi.fn();

        const cleanup = registerCapacitorAndroidBackHandler({
            appPlugin,
            capacitor: createCapacitorRuntime(),
            onNavigateBack,
        });

        expect(backButtonListener).not.toBeNull();
        if (typeof backButtonListener !== "function") {
            throw new Error("Expected backButtonListener to be registered");
        }
        const dispatchBackButton = backButtonListener as (event: BackButtonEvent) => void;

        dispatchBackButton({ canGoBack: false });
        expect(onNavigateBack).not.toHaveBeenCalled();

        dispatchBackButton({ canGoBack: true });
        expect(onNavigateBack).toHaveBeenCalledTimes(1);

        cleanup();
        await Promise.resolve();
        expect(remove).toHaveBeenCalledTimes(1);
    });

    it("removes the listener if cleanup runs before async registration resolves", async () => {
        const remove = vi.fn<PluginListenerHandle["remove"]>().mockResolvedValue(undefined);
        let resolveListenerHandle: ((handle: PluginListenerHandle) => void) | null = null;

        const appPlugin: CapacitorAppPlugin = {
            addListener: vi.fn(() => {
                return new Promise<PluginListenerHandle>((resolve) => {
                    resolveListenerHandle = resolve;
                });
            }),
        };

        const cleanup = registerCapacitorAndroidBackHandler({
            appPlugin,
            capacitor: createCapacitorRuntime(),
        });

        cleanup();

        if (typeof resolveListenerHandle !== "function") {
            throw new Error("Expected async listener registration to provide a resolver");
        }
        const resolveAsyncListenerHandle = resolveListenerHandle as (handle: PluginListenerHandle) => void;

        resolveAsyncListenerHandle({ remove });
        await Promise.resolve();
        await Promise.resolve();

        expect(remove).toHaveBeenCalledTimes(1);
    });
});
