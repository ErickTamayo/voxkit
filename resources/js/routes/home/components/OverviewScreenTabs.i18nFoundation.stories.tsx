import type { FC } from "react";
import type { Meta, StoryObj } from "@storybook/react-vite";
import { useTranslation } from "react-i18next";

const OverviewI18nFoundationStory: FC = () => {
    const { t } = useTranslation();

    return (
        <main className="bg-background min-h-full p-4">
            <div className="mx-auto flex min-h-[calc(100dvh-2rem)] w-full max-w-4xl flex-col gap-4">
                <header className="space-y-2 px-1 pt-2">
                    <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                        Home / Capacitor
                    </p>
                    <h1 className="text-2xl font-semibold">
                        {t("Overview i18n baseline")}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {t("Translation runtime is wired for the overview screen.")}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        {t("Active tab: {{tab}}", { tab: t("Reports") })}
                    </p>
                </header>
            </div>
        </main>
    );
};

const meta = {
    title: "Screens/Home/Capacitor/Overview I18n (Foundation)",
    component: OverviewI18nFoundationStory,
    parameters: {
        layout: "fullscreen",
    },
} satisfies Meta<typeof OverviewI18nFoundationStory>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Smoke: Story = {
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
