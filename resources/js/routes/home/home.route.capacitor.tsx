import { Suspense, useEffect, useState, type FC } from "react";
import { ErrorBoundary, type FallbackProps } from "react-error-boundary";
import { useLocation } from "wouter";
import { Button } from "@/components/ui/button";
import { useSession } from "@/hooks/useSession";
import { OverviewHomeHeader } from "@/routes/home/components/OverviewHomeHeader";
import { OverviewScreenTabs } from "@/routes/home/components/OverviewScreenTabs/OverviewScreenTabs";
import type {
    OverviewScreenTabDefinition,
    OverviewScreenTabModule,
} from "@/routes/home/components/OverviewScreenTabs/types";
import {
    createOverviewConsoleNavigationHandlers,
    OverviewNavigationProvider,
    type OverviewNavigationHandlers,
} from "@/routes/home/components/overviewNavigation";
import { OverviewMenuDrawerOverlay } from "@/routes/home/components/overlays/OverviewMenuDrawerOverlay";
import { OverviewSearchOverlay } from "@/routes/home/components/overlays/OverviewSearchOverlay";
import { OverviewSettingsOverlay } from "@/routes/home/components/overlays/OverviewSettingsOverlay";

const loadReportsOverviewScreen = async (): Promise<OverviewScreenTabModule> => {
    const module = await import(
        "@/routes/home/components/OverviewScreenTabs/reports/ReportsScreen"
    );

    return { default: module.ReportsScreen };
};

const loadActivitiesOverviewScreen = async (): Promise<OverviewScreenTabModule> => {
    const module = await import(
        "@/routes/home/components/OverviewScreenTabs/activities/ActivitiesScreen"
    );

    return { default: module.ActivitiesScreen };
};

const OVERVIEW_SCREEN_TABS: OverviewScreenTabDefinition[] = [
    {
        value: "reports",
        label: "Reports",
        loadScreen: loadReportsOverviewScreen,
        loadingFallback: (
            <p className="text-sm text-muted-foreground">Loading reports...</p>
        ),
    },
    {
        value: "activities",
        label: "Activities",
        loadScreen: loadActivitiesOverviewScreen,
        loadingFallback: (
            <p className="text-sm text-muted-foreground">Loading activities...</p>
        ),
    },
];
type OverviewCapacitorOverlay = "menu" | "none" | "search" | "settings";

interface HomeRouteCapacitorProps {
    initialOverlay?: OverviewCapacitorOverlay;
}

const OverviewHeaderSuspenseFallback: FC = () => {
    return (
        <header className="border-b border-border/70 bg-background px-4 py-3">
            <p className="text-sm text-muted-foreground">Loading header...</p>
        </header>
    );
};

const OverviewHeaderErrorFallback: FC<FallbackProps> = ({
    error,
    resetErrorBoundary,
}) => {
    const errorMessage = error instanceof Error ? error.message : "Unknown header error.";

    return (
        <header className="border-b border-border/70 bg-background px-4 py-3">
            <div className="flex items-center justify-between gap-2">
                <p className="text-sm text-destructive">
                    Header failed to load.
                </p>
                <Button type="button" variant="outline" size="sm" onClick={() => resetErrorBoundary()}>
                    Retry
                </Button>
            </div>
            <p className="mt-2 text-xs text-muted-foreground">{errorMessage}</p>
        </header>
    );
};

const HomeRouteCapacitor: FC<HomeRouteCapacitorProps> = ({
    initialOverlay = "none",
}) => {
    const [, setLocation] = useLocation();
    const { status } = useSession();
    const [activeOverlay, setActiveOverlay] = useState<OverviewCapacitorOverlay>(
        initialOverlay,
    );

    useEffect(() => {
        setActiveOverlay(initialOverlay);
    }, [initialOverlay]);

    const defaultNavigationHandlers = createOverviewConsoleNavigationHandlers();
    const overviewNavigationHandlers: OverviewNavigationHandlers = {
        ...defaultNavigationHandlers,
        onOpenSearch: () => setActiveOverlay("search"),
        onOpenMenuDrawer: () => setActiveOverlay("menu"),
        onOpenSettings: () => setActiveOverlay("settings"),
    };
    const isOverviewHeaderEnabled = true;

    if (status !== "authenticated") {
        return (
            <main className="grid min-h-screen place-items-center bg-muted p-6">
                <div className="w-full max-w-xl space-y-4">
                    <section className="space-y-6 rounded-xl border bg-card p-8 text-card-foreground shadow-xs">
                        <div className="space-y-2">
                            <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                                Voxkit (Capacitor)
                            </p>
                            <h1 className="text-3xl font-semibold text-balance">
                                Welcome
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                Sign in to access the native overview experience.
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                onClick={() => setLocation("/signin")}
                            >
                                Sign in
                            </Button>
                        </div>
                    </section>
                </div>
            </main>
        );
    }

    return (
        <main className="bg-background h-full min-h-full overflow-hidden">
            <div className="flex h-full min-h-0 w-full flex-col">
                <OverviewNavigationProvider handlers={overviewNavigationHandlers}>
                    {isOverviewHeaderEnabled ? (
                        <ErrorBoundary FallbackComponent={OverviewHeaderErrorFallback}>
                            <Suspense fallback={<OverviewHeaderSuspenseFallback />}>
                                <OverviewHomeHeader
                                    onOpenSearch={overviewNavigationHandlers.onOpenSearch}
                                    onOpenMenuDrawer={overviewNavigationHandlers.onOpenMenuDrawer}
                                    onOpenSettings={overviewNavigationHandlers.onOpenSettings}
                                />
                            </Suspense>
                        </ErrorBoundary>
                    ) : null}
                    <OverviewScreenTabs
                        tabs={OVERVIEW_SCREEN_TABS}
                        initialValue="reports"
                        className="min-h-0 flex-1 overflow-hidden"
                    />

                    <OverviewSearchOverlay
                        open={activeOverlay === "search"}
                        onOpenChange={(open) => {
                            setActiveOverlay(open ? "search" : "none");
                        }}
                    />
                    <OverviewSettingsOverlay
                        open={activeOverlay === "settings"}
                        onOpenChange={(open) => {
                            setActiveOverlay(open ? "settings" : "none");
                        }}
                    />
                    <OverviewMenuDrawerOverlay
                        open={activeOverlay === "menu"}
                        onOpenChange={(open) => {
                            setActiveOverlay(open ? "menu" : "none");
                        }}
                    />
                </OverviewNavigationProvider>
            </div>
        </main>
    );
};

export default HomeRouteCapacitor;
