import { useState, type FC, type ReactNode } from "react";
import type { Meta, StoryObj } from "@storybook/react-vite";
import { Button } from "@/components/ui/button";
import { Modal } from "@/components/ui/modal";

interface ModalStoryDemoProps {
    footer?: ReactNode;
    headerCenterTop?: ReactNode;
    headerLeft?: ReactNode;
    headerRight?: ReactNode;
    showHandle?: boolean;
}

const ModalStoryDemo: FC<ModalStoryDemoProps> = ({
    footer,
    headerCenterTop,
    headerLeft,
    headerRight,
    showHandle = true,
}) => {
    const [open, setOpen] = useState<boolean>(true);

    return (
        <main className="relative min-h-full bg-muted/40 p-4 md:p-8">
            <div className="mx-auto flex w-full max-w-4xl flex-col gap-4">
                <div className="rounded-xl border border-border bg-background p-4 shadow-sm">
                    <p className="text-sm text-muted-foreground">
                        Modal story canvas. Use Storybook viewport presets and the `Target` / `Safe Area` toolbar controls to preview sheet vs dialog presentation.
                    </p>
                    <div className="mt-3 flex flex-wrap gap-2">
                        <Button type="button" onClick={() => setOpen(true)}>
                            Open modal
                        </Button>
                        <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                            Close modal
                        </Button>
                    </div>
                </div>

                <div className="rounded-xl border border-dashed border-border bg-background p-6">
                    <p className="text-sm font-medium">Underlying page content</p>
                    <p className="mt-2 text-sm text-muted-foreground">
                        This content helps inspect overlay coverage and portal layering while the modal is open.
                    </p>
                    <div className="mt-4 grid gap-3 md:grid-cols-2">
                        <div className="rounded-lg border border-border bg-muted/40 p-4">
                            <p className="text-sm font-medium">Agent Profile</p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Name, contact methods, availability, rates, and tags.
                            </p>
                        </div>
                        <div className="rounded-lg border border-border bg-muted/40 p-4">
                            <p className="text-sm font-medium">Project Notes</p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Internal casting notes, deadlines, and follow-up reminders.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <Modal.Root open={open} onOpenChange={setOpen}>
                <Modal.Overlay />
                <Modal.Positioner>
                    <Modal.Content>
                        {showHandle ? <Modal.Handle /> : null}

                        <Modal.Header>
                            <Modal.HeaderLeft>
                                {headerLeft}
                            </Modal.HeaderLeft>

                            <Modal.HeaderCenter>
                                {headerCenterTop}
                                <Modal.Title>Edit agent status</Modal.Title>
                                <Modal.Description>
                                    Update availability and leave a short note for the team.
                                </Modal.Description>
                            </Modal.HeaderCenter>

                            <Modal.HeaderRight>
                                {headerRight}
                            </Modal.HeaderRight>
                        </Modal.Header>

                        <Modal.Body>
                            <Modal.SafeAreaContent>
                                <div className="space-y-4">
                                    <section className="space-y-2">
                                        <p className="text-sm font-medium">Availability</p>
                                        <div className="grid gap-2 sm:grid-cols-2">
                                            <Button type="button" variant="outline" className="justify-start">
                                                Available this week
                                            </Button>
                                            <Button type="button" variant="outline" className="justify-start">
                                                On hold
                                            </Button>
                                        </div>
                                    </section>

                                    <section className="space-y-2">
                                        <p className="text-sm font-medium">Internal note</p>
                                        <div className="rounded-lg border border-border bg-muted/40 p-3 text-sm text-muted-foreground">
                                            Prefers remote sessions, available after 3 PM, and can self-record auditions within 24 hours.
                                        </div>
                                    </section>
                                </div>
                            </Modal.SafeAreaContent>
                        </Modal.Body>

                        {footer}
                    </Modal.Content>
                </Modal.Positioner>
            </Modal.Root>
        </main>
    );
};

const desktopFooter = (
    <Modal.Footer>
        <div className="flex items-center justify-end gap-2">
            <Modal.Close asChild>
                <Button type="button" variant="outline">
                    Cancel
                </Button>
            </Modal.Close>
            <Modal.Close asChild>
                <Button type="button">
                    Confirm
                </Button>
            </Modal.Close>
        </div>
    </Modal.Footer>
);

const meta = {
    title: "UI/Modal",
    component: ModalStoryDemo,
    parameters: {
        layout: "fullscreen",
    },
} satisfies Meta<typeof ModalStoryDemo>;

export default meta;

type Story = StoryObj<typeof meta>;

export const DesktopFooterModal: Story = {
    args: {
        footer: desktopFooter,
        showHandle: false,
        headerRight: <Modal.CloseButton />,
    },
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

export const MobileHeaderActionsSheet: Story = {
    args: {
        footer: undefined,
        showHandle: false,
        headerCenterTop: <Modal.Handle className="relative -top-2 px-0 pt-0 pb-5" />,
        headerLeft: <Modal.CloseButton />,
        headerRight: (
            <Modal.Close asChild>
                <Modal.ConfirmButton />
            </Modal.Close>
        ),
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

export const ResponsiveSingleComposition: Story = {
    args: {
        showHandle: false,
        headerCenterTop: <Modal.Handle className="relative -top-2 px-0 pt-0 pb-5 md:hidden" />,
        headerLeft: <Modal.CloseButton className="md:hidden" />,
        headerRight: (
            <>
                <Modal.Close asChild>
                    <Modal.ConfirmButton className="md:hidden" />
                </Modal.Close>
                <Modal.CloseButton className="hidden md:inline-flex" />
            </>
        ),
        footer: (
            <Modal.Footer className="hidden md:block">
                <div className="flex items-center justify-end gap-2">
                    <Modal.Close asChild>
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Modal.Close>
                    <Modal.Close asChild>
                        <Button type="button">
                            Confirm
                        </Button>
                    </Modal.Close>
                </div>
            </Modal.Footer>
        ),
    },
    globals: {
        platformTarget: "web",
        safeAreaPreset: "none",
    },
    parameters: {
        viewport: {
            defaultViewport: "mobileSheet",
        },
    },
};
