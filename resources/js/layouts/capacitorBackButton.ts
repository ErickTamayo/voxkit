interface PluginListenerHandle {
    remove: () => Promise<void>;
}

interface BackButtonEvent {
    canGoBack: boolean;
}

interface CapacitorAppPlugin {
    addListener: (
        eventName: "backButton",
        listenerFunc: (event: BackButtonEvent) => void,
    ) => PluginListenerHandle | Promise<PluginListenerHandle>;
}

interface CapacitorRuntime {
    getPlatform: () => string;
    isNativePlatform: () => boolean;
}

interface RegisterCapacitorAndroidBackHandlerOptions {
    appPlugin: CapacitorAppPlugin;
    capacitor: CapacitorRuntime;
    onNavigateBack?: () => void;
}

export type {
    BackButtonEvent,
    CapacitorAppPlugin,
    CapacitorRuntime,
    PluginListenerHandle,
    RegisterCapacitorAndroidBackHandlerOptions,
};

export function shouldRegisterCapacitorAndroidBackHandler(capacitor: CapacitorRuntime): boolean {
    return capacitor.isNativePlatform() && capacitor.getPlatform() === "android";
}

export function registerCapacitorAndroidBackHandler({
    appPlugin,
    capacitor,
    onNavigateBack = () => {
        window.history.back();
    },
}: RegisterCapacitorAndroidBackHandlerOptions): () => void {
    if (!shouldRegisterCapacitorAndroidBackHandler(capacitor)) {
        return () => {};
    }

    let isDisposed = false;
    let listenerHandle: PluginListenerHandle | null = null;

    void Promise.resolve(
        appPlugin.addListener("backButton", ({ canGoBack }) => {
            if (canGoBack) {
                onNavigateBack();
            }
            // At the app root, intentionally no-op for now.
        }),
    )
        .then((handle) => {
            if (isDisposed) {
                void handle.remove();
                return;
            }

            listenerHandle = handle;
        })
        .catch(() => {
            // Keep root layout initialization resilient if the native App plugin is unavailable.
        });

    return () => {
        isDisposed = true;

        if (listenerHandle !== null) {
            void listenerHandle.remove();
        }
    };
}
