import type { FC } from "react";
import type { Meta, StoryObj } from "@storybook/react-vite";

const HomeWebOverviewPlaceholderStory: FC = () => {
    return (
        <main className="bg-background min-h-full p-6 md:p-8">
            <div className="mx-auto flex min-h-[calc(100dvh-3rem)] w-full max-w-6xl flex-col gap-6">
                <header className="space-y-2">
                    <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                        Home / Web
                    </p>
                    <h1 className="text-3xl font-semibold">Overview</h1>
                    <p className="text-sm text-muted-foreground">
                        Empty Storybook placeholder for the web overview screen.
                        Capacitor tab work continues separately.
                    </p>
                </header>

                <section className="grid min-h-0 flex-1 gap-4 md:grid-cols-[1.2fr_0.8fr]">
                    <div className="rounded-2xl border border-dashed border-border/80 bg-card/70 p-6">
                        <p className="text-sm font-medium text-foreground">
                            Main overview content (placeholder)
                        </p>
                    </div>

                    <div className="grid gap-4">
                        <div className="rounded-2xl border border-dashed border-border/80 bg-card/70 p-6">
                            <p className="text-sm font-medium text-foreground">
                                Sidebar panel (placeholder)
                            </p>
                        </div>
                        <div className="rounded-2xl border border-dashed border-border/80 bg-card/70 p-6">
                            <p className="text-sm font-medium text-foreground">
                                Secondary panel (placeholder)
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    );
};

const meta = {
    title: "Screens/Home/Web/Overview",
    component: HomeWebOverviewPlaceholderStory,
    parameters: {
        layout: "fullscreen",
    },
} satisfies Meta<typeof HomeWebOverviewPlaceholderStory>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Empty: Story = {
    globals: {
        platformTarget: "web",
        safeAreaPreset: "none",
    },
    parameters: {
        viewport: {
            defaultViewport: "desktopDialog",
        },
    },
};
