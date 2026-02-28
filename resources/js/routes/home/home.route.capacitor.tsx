import type { FC } from "react";
import { useLocation } from "wouter";
import { Button } from "@/components/ui/button";
import { useSession } from "@/hooks/useSession";
import { OverviewScreenTabs } from "@/routes/home/components/OverviewScreenTabs/OverviewScreenTabs";
import type {
    OverviewScreenTabDefinition,
    OverviewScreenTabModule,
} from "@/routes/home/components/OverviewScreenTabs/types";

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

const HomeRouteCapacitor: FC = () => {
    const [, setLocation] = useLocation();
    const { status } = useSession();

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
                <header className="space-y-2 px-4 pb-3 pt-4">
                    <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                        Capacitor Overview
                    </p>
                    <h1 className="text-2xl font-semibold">Home</h1>
                    <p className="text-sm text-muted-foreground">
                        Scaffold for the swipeable overview tab pattern. Web and
                        Capacitor implementations intentionally diverge.
                    </p>
                </header>

                <OverviewScreenTabs
                    tabs={OVERVIEW_SCREEN_TABS}
                    initialValue="reports"
                    className="min-h-0 flex-1 overflow-hidden"
                />
            </div>
        </main>
    );
};

export default HomeRouteCapacitor;
