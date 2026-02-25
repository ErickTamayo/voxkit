import { useEffect, type CSSProperties, type FC, type ReactNode } from "react";
import type { Preview } from "@storybook/react-vite";
import "../resources/css/app.css";

type PlatformTarget = "web" | "capacitor";
type SafeAreaPreset = "none" | "iphone";

const SAFE_AREA_PRESETS: Record<SafeAreaPreset, {
    top: string;
    right: string;
    bottom: string;
    left: string;
}> = {
    none: {
        top: "0px",
        right: "0px",
        bottom: "0px",
        left: "0px",
    },
    iphone: {
        top: "59px",
        right: "0px",
        bottom: "34px",
        left: "0px",
    },
};

interface StorybookPlatformFrameProps {
    children: ReactNode;
    platformTarget: PlatformTarget;
    safeArea: {
        top: string;
        right: string;
        bottom: string;
        left: string;
    };
}

const StorybookPlatformFrame: FC<StorybookPlatformFrameProps> = ({
    children,
    platformTarget,
    safeArea,
}) => {
    useEffect(() => {
        if (typeof document === "undefined") {
            return;
        }

        const root = document.documentElement;
        const previousValues = {
            top: root.style.getPropertyValue("--safe-area-top"),
            right: root.style.getPropertyValue("--safe-area-right"),
            bottom: root.style.getPropertyValue("--safe-area-bottom"),
            left: root.style.getPropertyValue("--safe-area-left"),
        };

        // Storybook stories render modals via portals (`document.body`), so fake safe-area vars
        // applied only to the story wrapper won't reach the portaled modal content. Mirror the
        // simulated values onto the document root so `safe-area-*` utilities work in stories.
        root.style.setProperty("--safe-area-top", safeArea.top);
        root.style.setProperty("--safe-area-right", safeArea.right);
        root.style.setProperty("--safe-area-bottom", safeArea.bottom);
        root.style.setProperty("--safe-area-left", safeArea.left);

        return () => {
            if (previousValues.top) {
                root.style.setProperty("--safe-area-top", previousValues.top);
            } else {
                root.style.removeProperty("--safe-area-top");
            }

            if (previousValues.right) {
                root.style.setProperty("--safe-area-right", previousValues.right);
            } else {
                root.style.removeProperty("--safe-area-right");
            }

            if (previousValues.bottom) {
                root.style.setProperty("--safe-area-bottom", previousValues.bottom);
            } else {
                root.style.removeProperty("--safe-area-bottom");
            }

            if (previousValues.left) {
                root.style.setProperty("--safe-area-left", previousValues.left);
            } else {
                root.style.removeProperty("--safe-area-left");
            }
        };
    }, [safeArea.bottom, safeArea.left, safeArea.right, safeArea.top]);

    const rootStyle = {
        "--safe-area-top": safeArea.top,
        "--safe-area-right": safeArea.right,
        "--safe-area-bottom": safeArea.bottom,
        "--safe-area-left": safeArea.left,
    } as CSSProperties;
    const rootClassName = [
        "app-root-viewport",
        "app-root-viewport-lock",
        "relative",
    ].filter(Boolean).join(" ");
    const safeFrameClassName = [
        "app-root-safe-frame",
        platformTarget === "capacitor" ? "safe-area-inset" : "",
    ].filter(Boolean).join(" ");

    return (
        <div className="min-h-screen text-foreground antialiased">
            <div
                data-storybook-platform-root=""
                data-app-target={platformTarget === "capacitor" ? "capacitor" : undefined}
                className={rootClassName}
                style={rootStyle}
            >
                <div className={safeFrameClassName}>
                    <div className="app-root-scroll-region">
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
};

const preview: Preview = {
    globalTypes: {
        platformTarget: {
            name: "Target",
            description: "Simulated runtime target wrapper for stories",
            defaultValue: "web",
            toolbar: {
                icon: "browser",
                items: [
                    { value: "web", title: "Web" },
                    { value: "capacitor", title: "Capacitor" },
                ],
                dynamicTitle: true,
            },
        },
        safeAreaPreset: {
            name: "Safe Area",
            description: "Fake safe-area CSS variables for previewing fixed surfaces",
            defaultValue: "none",
            toolbar: {
                icon: "mirror",
                items: [
                    { value: "none", title: "No Insets" },
                    { value: "iphone", title: "iPhone Insets" },
                ],
                dynamicTitle: true,
            },
        },
    },
    parameters: {
        layout: "fullscreen",
        backgrounds: {
            default: "app-surface",
            values: [
                {
                    name: "app-surface",
                    value: "#0a0d12",
                },
                {
                    name: "light",
                    value: "#ffffff",
                },
            ],
        },
        viewport: {
            options: {
                mobileSheet: {
                    name: "Mobile (393x852)",
                    styles: {
                        width: "393px",
                        height: "852px",
                    },
                    type: "mobile",
                },
                desktopDialog: {
                    name: "Desktop (1440x900)",
                    styles: {
                        width: "1440px",
                        height: "900px",
                    },
                    type: "desktop",
                },
            },
        },
    },
    decorators: [
        (Story, context) => {
            const platformTarget = (context.globals.platformTarget ?? "web") as PlatformTarget;
            const safeAreaPreset = (context.globals.safeAreaPreset ?? "none") as SafeAreaPreset;
            const safeArea = SAFE_AREA_PRESETS[safeAreaPreset] ?? SAFE_AREA_PRESETS.none;

            return (
                <StorybookPlatformFrame platformTarget={platformTarget} safeArea={safeArea}>
                    <Story />
                </StorybookPlatformFrame>
            );
        },
    ],
};

export default preview;
