import type { FC } from "react";
import type { Meta, StoryObj } from "@storybook/react-vite";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";

const ToasterFoundationStory: FC = () => {
    return (
        <main className="bg-background min-h-full p-4">
            <div className="mx-auto flex min-h-[calc(100dvh-2rem)] w-full max-w-4xl flex-col gap-4">
                <header className="space-y-2 px-1 pt-2">
                    <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                        Foundation
                    </p>
                    <h1 className="text-2xl font-semibold">Toaster Baseline</h1>
                    <p className="text-sm text-muted-foreground">
                        Smoke story for success and error toast behavior.
                    </p>
                </header>

                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        onClick={() => toast.success("Activity archived.")}
                    >
                        Trigger Success Toast
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => toast.error("Failed to archive activity.")}
                    >
                        Trigger Error Toast
                    </Button>
                </div>
            </div>
        </main>
    );
};

const meta = {
    title: "Foundation/Toaster",
    component: ToasterFoundationStory,
    parameters: {
        layout: "fullscreen",
    },
} satisfies Meta<typeof ToasterFoundationStory>;

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
