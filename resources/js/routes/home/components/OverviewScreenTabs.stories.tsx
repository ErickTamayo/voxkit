import { useState, type FC } from "react";
import type { Meta, StoryObj } from "@storybook/react-vite";
import { OverviewScreenTabs } from "@/routes/home/components/OverviewScreenTabs/OverviewScreenTabs";
import type {
    OverviewScreenTabDefinition,
    OverviewScreenTabModule,
} from "@/routes/home/components/OverviewScreenTabs/types";

const ReportsStoryScreen: FC = () => {
    return (
        <section className="space-y-2">
            <h2 className="text-lg font-semibold">Reports</h2>
            <p className="text-sm text-muted-foreground">
                Story placeholder for the reports tab screen.
            </p>
        </section>
    );
};

const InboxStoryScreen: FC = () => {
    return (
        <section className="space-y-2">
            <h2 className="text-lg font-semibold">Inbox</h2>
            <p className="text-sm text-muted-foreground">
                Story placeholder for the inbox tab screen.
            </p>
        </section>
    );
};

const ActivityStoryScreen: FC = () => {
    return (
        <section className="space-y-2">
            <h2 className="text-lg font-semibold">Activity</h2>
            <p className="text-sm text-muted-foreground">
                Story placeholder for a third tab to test tab bar layout width.
            </p>
        </section>
    );
};

const loadReportsStoryScreen = async (): Promise<OverviewScreenTabModule> => {
    return { default: ReportsStoryScreen };
};

const loadInboxStoryScreen = async (): Promise<OverviewScreenTabModule> => {
    return { default: InboxStoryScreen };
};

const loadActivityStoryScreen = async (): Promise<OverviewScreenTabModule> => {
    return { default: ActivityStoryScreen };
};

const storyTabs: OverviewScreenTabDefinition[] = [
    {
        value: "reports",
        label: "Reports",
        loadScreen: loadReportsStoryScreen,
        loadingFallback: (
            <p className="text-sm text-muted-foreground">Loading reports...</p>
        ),
    },
    {
        value: "inbox",
        label: "Inbox",
        loadScreen: loadInboxStoryScreen,
        loadingFallback: (
            <p className="text-sm text-muted-foreground">Loading inbox...</p>
        ),
    },
    {
        value: "activity",
        label: "Activity",
        loadScreen: loadActivityStoryScreen,
        loadingFallback: (
            <p className="text-sm text-muted-foreground">Loading activity...</p>
        ),
    },
];

const OverviewScreenTabsStoryDemo: FC = () => {
    const [activeTab, setActiveTab] = useState<string>("reports");

    return (
        <main className="bg-background min-h-full p-4">
            <div className="mx-auto flex min-h-[calc(100dvh-2rem)] w-full max-w-4xl flex-col gap-4">
                <header className="space-y-2 px-1 pt-2">
                    <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                        Home / Capacitor
                    </p>
                    <h1 className="text-2xl font-semibold">Overview Tabs</h1>
                    <p className="text-sm text-muted-foreground">
                        Storybook preview for the Capacitor-only overview tab shell.
                        Lazy tab loading, error boundaries, and swipe behavior will be
                        added in later steps.
                    </p>
                    <p className="text-xs text-muted-foreground">
                        Active tab: <span className="font-medium text-foreground">{activeTab}</span>
                    </p>
                </header>

                <OverviewScreenTabs
                    tabs={storyTabs}
                    value={activeTab}
                    onValueChange={setActiveTab}
                    className="min-h-0 flex-1"
                />
            </div>
        </main>
    );
};

const meta = {
    title: "Screens/Home/Capacitor/Overview Tabs",
    component: OverviewScreenTabsStoryDemo,
    parameters: {
        layout: "fullscreen",
    },
} satisfies Meta<typeof OverviewScreenTabsStoryDemo>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Scaffold: Story = {
    globals: {
        platformTarget: "capacitor",
        safeAreaPreset: "iphone",
    },
    parameters: {
        viewport: {
            defaultViewport: "mobileSheet",
        },
    },
};

export const EmptyTabs: Story = {
    render: () => {
        return (
            <main className="bg-background min-h-full p-4">
                <div className="mx-auto flex min-h-[calc(100dvh-2rem)] w-full max-w-4xl flex-col gap-4">
                    <header className="space-y-2 px-1 pt-2">
                        <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                            Home / Capacitor
                        </p>
                        <h1 className="text-2xl font-semibold">Overview Tabs</h1>
                        <p className="text-sm text-muted-foreground">
                            Empty-state preview for the tabs scaffold while wiring the
                            feature.
                        </p>
                    </header>

                    <OverviewScreenTabs tabs={[]} className="min-h-0 flex-1" />
                </div>
            </main>
        );
    },
    globals: {
        platformTarget: "capacitor",
        safeAreaPreset: "iphone",
    },
    parameters: {
        viewport: {
            defaultViewport: "mobileSheet",
        },
    },
};
