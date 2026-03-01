import type { FC } from "react";
import * as DialogPrimitive from "@radix-ui/react-dialog";
import { X } from "lucide-react";
import { AnimatePresence, motion } from "motion/react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

interface OverviewMenuDrawerOverlayProps {
    onOpenChange: (open: boolean) => void;
    open: boolean;
}

const MENU_ITEMS = ["Profile", "Settings", "Billing", "Help"] as const;

const OverviewMenuDrawerOverlay: FC<OverviewMenuDrawerOverlayProps> = ({
    onOpenChange,
    open,
}) => {
    return (
        <DialogPrimitive.Root open={open} onOpenChange={onOpenChange}>
            <AnimatePresence>
                {open ? (
                    <DialogPrimitive.Portal forceMount>
                        <DialogPrimitive.Overlay asChild forceMount>
                            <motion.div
                                className="fixed inset-0 z-50 bg-black/45 backdrop-blur-[1px]"
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                exit={{ opacity: 0 }}
                                transition={{ duration: 0.16, ease: "easeOut" }}
                            />
                        </DialogPrimitive.Overlay>

                        <DialogPrimitive.Content
                            asChild
                            forceMount
                            onCloseAutoFocus={(event) => {
                                event.preventDefault();
                            }}
                        >
                            <motion.aside
                                className={cn(
                                    "safe-area-inset-top safe-area-inset-bottom fixed inset-y-0 right-0 z-50",
                                    "flex w-[min(85vw,22rem)] flex-col border-l border-border/80 bg-card shadow-2xl",
                                )}
                                initial={{ x: "100%" }}
                                animate={{ x: 0 }}
                                exit={{ x: "100%" }}
                                transition={{
                                    type: "spring",
                                    stiffness: 430,
                                    damping: 38,
                                    mass: 0.82,
                                }}
                            >
                                <header className="flex items-center justify-between border-b border-border/70 px-4 py-3">
                                    <DialogPrimitive.Title className="text-base font-semibold">
                                        Menu
                                    </DialogPrimitive.Title>
                                    <DialogPrimitive.Close asChild>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label="Close menu"
                                        >
                                            <X className="size-4" />
                                        </Button>
                                    </DialogPrimitive.Close>
                                </header>

                                <div className="flex-1 overflow-y-auto px-3 py-3">
                                    <nav className="space-y-1">
                                        {MENU_ITEMS.map((item) => {
                                            return (
                                                <button
                                                    key={item}
                                                    type="button"
                                                    className="hover:bg-accent flex w-full items-center rounded-md px-3 py-2 text-left text-sm transition-colors"
                                                >
                                                    {item}
                                                </button>
                                            );
                                        })}
                                    </nav>
                                </div>
                            </motion.aside>
                        </DialogPrimitive.Content>
                    </DialogPrimitive.Portal>
                ) : null}
            </AnimatePresence>
        </DialogPrimitive.Root>
    );
};

export { OverviewMenuDrawerOverlay };
export type { OverviewMenuDrawerOverlayProps };
