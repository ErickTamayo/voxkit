import { Capacitor, registerPlugin } from "@capacitor/core";
import { useEffect, type FC, type PropsWithChildren } from "react";
import { registerCapacitorAndroidBackHandler, type BackButtonEvent, type CapacitorAppPlugin, type PluginListenerHandle } from "@/layouts/capacitorBackButton";
import { useApplyDocumentAppTarget } from "@/layouts/hooks/useApplyDocumentAppTarget";

interface RootLayoutProps extends PropsWithChildren {}

const CapacitorApp = registerPlugin<CapacitorAppPlugin>("App");

function useCapacitorAndroidBackHandler(): void {
    useEffect(() => {
        return registerCapacitorAndroidBackHandler({
            appPlugin: CapacitorApp,
            capacitor: Capacitor,
        });
    }, []);
}

const RootLayout: FC<RootLayoutProps> = ({ children }) => {
    useCapacitorAndroidBackHandler();
    useApplyDocumentAppTarget("capacitor");

    return (
        <div
            className="app-root-viewport app-root-viewport-lock"
            data-app-target="capacitor"
        >
            <div className="app-root-safe-frame safe-area-inset-top safe-area-inset-x">
                <div className="app-root-scroll-region">
                    {children}
                </div>
            </div>
        </div>
    );
};

export default RootLayout;
