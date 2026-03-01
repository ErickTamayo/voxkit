import type { FC } from "react";
import { Bell, ShieldCheck, UserRound } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Modal } from "@/components/ui/modal";

interface OverviewSettingsOverlayProps {
    onOpenChange: (open: boolean) => void;
    open: boolean;
}

const OverviewSettingsOverlay: FC<OverviewSettingsOverlayProps> = ({
    onOpenChange,
    open,
}) => {
    return (
        <Modal.Root open={open} onOpenChange={onOpenChange}>
            <Modal.Overlay />
            <Modal.Positioner>
                <Modal.Content className="md:w-[min(100%-2rem,34rem)]">
                    <Modal.Handle />

                    <Modal.Header>
                        <Modal.HeaderLeft>
                            <Modal.CloseButton />
                        </Modal.HeaderLeft>
                        <Modal.HeaderCenter>
                            <Modal.Title>Settings</Modal.Title>
                            <Modal.Description>
                                Settings surface scaffold for the Capacitor overview
                                flow.
                            </Modal.Description>
                        </Modal.HeaderCenter>
                        <Modal.HeaderRight />
                    </Modal.Header>

                    <Modal.Body>
                        <Modal.SafeAreaContent className="space-y-3">
                            <button
                                type="button"
                                className="flex w-full items-center justify-between rounded-lg border border-border px-3 py-3 text-left"
                            >
                                <span className="inline-flex items-center gap-2 text-sm font-medium">
                                    <UserRound className="size-4 text-muted-foreground" />
                                    Profile
                                </span>
                                <span className="text-xs text-muted-foreground">Open</span>
                            </button>

                            <button
                                type="button"
                                className="flex w-full items-center justify-between rounded-lg border border-border px-3 py-3 text-left"
                            >
                                <span className="inline-flex items-center gap-2 text-sm font-medium">
                                    <Bell className="size-4 text-muted-foreground" />
                                    Notifications
                                </span>
                                <span className="text-xs text-muted-foreground">Open</span>
                            </button>

                            <button
                                type="button"
                                className="flex w-full items-center justify-between rounded-lg border border-border px-3 py-3 text-left"
                            >
                                <span className="inline-flex items-center gap-2 text-sm font-medium">
                                    <ShieldCheck className="size-4 text-muted-foreground" />
                                    Security
                                </span>
                                <span className="text-xs text-muted-foreground">Open</span>
                            </button>
                        </Modal.SafeAreaContent>
                    </Modal.Body>

                    <Modal.Footer>
                        <div className="flex justify-end">
                            <Modal.Close asChild>
                                <Button type="button" variant="outline">
                                    Done
                                </Button>
                            </Modal.Close>
                        </div>
                    </Modal.Footer>
                </Modal.Content>
            </Modal.Positioner>
        </Modal.Root>
    );
};

export { OverviewSettingsOverlay };
export type { OverviewSettingsOverlayProps };
