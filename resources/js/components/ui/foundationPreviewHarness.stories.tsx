import { useEffect, useState, type FC } from "react";
import type { Meta, StoryObj } from "@storybook/react-vite";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

interface HarnessSnapshot {
    cssSafeAreaBottom: string;
    cssSafeAreaLeft: string;
    cssSafeAreaRight: string;
    cssSafeAreaTop: string;
    rootDataTarget: string | null;
    rootPaddingBottom: string;
    rootPaddingLeft: string;
    rootPaddingRight: string;
    rootPaddingTop: string;
}

function readHarnessSnapshot(): HarnessSnapshot {
    if (typeof window === "undefined") {
        return {
            cssSafeAreaTop: "n/a",
            cssSafeAreaRight: "n/a",
            cssSafeAreaBottom: "n/a",
            cssSafeAreaLeft: "n/a",
            rootPaddingTop: "n/a",
            rootPaddingRight: "n/a",
            rootPaddingBottom: "n/a",
            rootPaddingLeft: "n/a",
            rootDataTarget: null,
        };
    }

    const rootLayoutElement = document.querySelector<HTMLElement>("[data-storybook-platform-root]");
    const rootStyle = rootLayoutElement !== null ? window.getComputedStyle(rootLayoutElement) : null;

    return {
        cssSafeAreaTop: rootStyle?.getPropertyValue("--safe-area-top").trim() || "0px",
        cssSafeAreaRight: rootStyle?.getPropertyValue("--safe-area-right").trim() || "0px",
        cssSafeAreaBottom: rootStyle?.getPropertyValue("--safe-area-bottom").trim() || "0px",
        cssSafeAreaLeft: rootStyle?.getPropertyValue("--safe-area-left").trim() || "0px",
        rootPaddingTop: rootStyle?.paddingTop ?? "0px",
        rootPaddingRight: rootStyle?.paddingRight ?? "0px",
        rootPaddingBottom: rootStyle?.paddingBottom ?? "0px",
        rootPaddingLeft: rootStyle?.paddingLeft ?? "0px",
        rootDataTarget: rootLayoutElement?.getAttribute("data-app-target") ?? null,
    };
}

const FoundationPreviewHarnessDemo: FC = () => {
    const [snapshot, setSnapshot] = useState<HarnessSnapshot>(() => readHarnessSnapshot());

    useEffect(() => {
        function refresh(): void {
            setSnapshot(readHarnessSnapshot());
        }

        refresh();
        window.addEventListener("resize", refresh);

        return () => {
            window.removeEventListener("resize", refresh);
        };
    }, []);

    return (
        <main className="relative min-h-full bg-muted/50 p-4 md:p-8">
            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6">
                <div className="rounded-lg border border-amber-300/70 bg-amber-50/90 p-3 text-xs text-amber-950 shadow-xs">
                    <p className="font-mono font-semibold">
                        Storybook Foundation Harness
                    </p>
                    <p className="mt-1 font-mono">
                        root data-app-target={snapshot.rootDataTarget ?? "null"}
                    </p>
                    <p className="mt-1 font-mono">
                        root padding t/r/b/l = {snapshot.rootPaddingTop} / {snapshot.rootPaddingRight} /{" "}
                        {snapshot.rootPaddingBottom} / {snapshot.rootPaddingLeft}
                    </p>
                    <p className="mt-1 font-mono">
                        css vars t/r/b/l = {snapshot.cssSafeAreaTop} / {snapshot.cssSafeAreaRight} /{" "}
                        {snapshot.cssSafeAreaBottom} / {snapshot.cssSafeAreaLeft}
                    </p>
                    <p className="mt-2 text-amber-800">
                        Use the Storybook toolbar to switch `Target` and `Safe Area`.
                    </p>
                </div>

                <Card className="shadow-xl">
                    <CardHeader>
                        <CardTitle>Modal Preview Workspace</CardTitle>
                        <CardDescription>
                            This is the canvas we will use to test modal presentation and safe-area behavior before integrating in routes.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="rounded-xl border border-dashed border-border bg-background p-6">
                            <p className="text-sm text-muted-foreground">
                                Placeholder story content. Next steps will render the actual modal here.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button type="button">Primary</Button>
                            <Button type="button" variant="outline">Secondary</Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="safe-area-inset-bottom pointer-events-none fixed inset-x-0 bottom-0">
                <div className="mx-auto mb-0 w-full max-w-4xl px-4 pb-4 md:px-8">
                    <div className="rounded-t-xl border border-border bg-background/90 p-3 shadow-lg backdrop-blur-sm">
                        <p className="text-xs text-muted-foreground">
                            Fixed surface demo (uses <code>safe-area-inset-bottom</code>)
                        </p>
                    </div>
                </div>
            </div>
        </main>
    );
};

const meta = {
    title: "Foundation/Preview Harness",
    component: FoundationPreviewHarnessDemo,
    parameters: {
        layout: "fullscreen",
    },
} satisfies Meta<typeof FoundationPreviewHarnessDemo>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Web: Story = {
    globals: {
        platformTarget: "web",
        safeAreaPreset: "none",
    },
};

export const CapacitorIPhone: Story = {
    globals: {
        platformTarget: "capacitor",
        safeAreaPreset: "iphone",
    },
};
