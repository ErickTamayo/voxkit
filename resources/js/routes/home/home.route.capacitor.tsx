import type { FC } from "react";
import { useLocation } from "wouter";
import { Button } from "@/components/ui/button";
import { useSession } from "@/hooks/useSession";
import { OverviewScreenTabs } from "@/routes/home/components/OverviewScreenTabs/OverviewScreenTabs";
import type {
    OverviewScreenTabDefinition,
    OverviewScreenTabModule,
} from "@/routes/home/components/OverviewScreenTabs/types";

const ReportsOverviewPlaceholderScreen: FC = () => {
    return (
        <section className="space-y-2">
            <h2 className="text-lg font-semibold">Reports</h2>
            <p className="text-sm text-muted-foreground">
                Placeholder tab screen module for the Capacitor overview tabs scaffold.
            </p>
        </section>
    );
};

const InboxOverviewPlaceholderScreen: FC = () => {
    return (
        <section className="space-y-2">
            <h2 className="text-lg font-semibold">Inbox</h2>
            <p className="text-sm text-muted-foreground">
                Placeholder tab screen module for the Capacitor overview tabs scaffold.
            </p>
        </section>
    );
};

const loadReportsOverviewPlaceholderScreen = async (): Promise<OverviewScreenTabModule> => {
    return { default: ReportsOverviewPlaceholderScreen };
};

const loadInboxOverviewPlaceholderScreen = async (): Promise<OverviewScreenTabModule> => {
    return { default: InboxOverviewPlaceholderScreen };
};

const OVERVIEW_SCREEN_TABS: OverviewScreenTabDefinition[] = [
    {
        value: "reports",
        label: "Reports",
        loadScreen: loadReportsOverviewPlaceholderScreen,
        loadingFallback: (
            <p className="text-sm text-muted-foreground">Loading reports...</p>
        ),
    },
    {
        value: "inbox",
        label: "Inbox",
        loadScreen: loadInboxOverviewPlaceholderScreen,
        loadingFallback: (
            <p className="text-sm text-muted-foreground">Loading inbox...</p>
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
        <main className="bg-background min-h-screen p-4">
            <div className="mx-auto flex min-h-[calc(100dvh-2rem)] w-full max-w-4xl flex-col gap-4">
                <header className="space-y-2 px-1 pt-2">
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
                    className="min-h-0 flex-1"
                />
            </div>
        </main>
    );
};

export default HomeRouteCapacitor;
