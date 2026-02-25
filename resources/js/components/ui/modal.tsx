import type { ComponentProps, FC, ReactNode } from "react";
import * as DialogPrimitive from "@radix-ui/react-dialog";
import { Check, X } from "lucide-react";
import { AnimatePresence, motion, type HTMLMotionProps } from "motion/react";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogClose,
    DialogDescription,
    DialogPortal,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    useModalRootContext,
    ModalRootContext,
} from "@/components/ui/modal/hooks/useModalRootContext";
import { useIsDesktopViewport } from "@/hooks/useIsDesktopViewport";
import { cn } from "@/lib/utils";

type RadixDialogRootProps = ComponentProps<typeof DialogPrimitive.Root>;

interface ModalRootProps {
    children: ReactNode;
    modal?: boolean;
    onOpenChange: NonNullable<RadixDialogRootProps["onOpenChange"]>;
    open: boolean;
}

interface ModalOverlayProps extends HTMLMotionProps<"div"> {}
interface ModalPositionerProps extends HTMLMotionProps<"div"> {}
interface ModalContentProps extends ComponentProps<"div"> {}
interface ModalHandleProps extends ComponentProps<"div"> {}
interface ModalHeaderProps extends ComponentProps<"div"> {}
interface ModalHeaderLeftProps extends ComponentProps<"div"> {}
interface ModalHeaderCenterProps extends ComponentProps<"div"> {}
interface ModalHeaderRightProps extends ComponentProps<"div"> {}
interface ModalBodyProps extends ComponentProps<"div"> {}
interface ModalFooterProps extends ComponentProps<"div"> {}
interface ModalSafeAreaContentProps extends ComponentProps<"div"> {}
interface ModalCloseButtonProps extends ComponentProps<
    typeof DialogPrimitive.Close
> {}
interface ModalConfirmButtonProps extends ComponentProps<typeof Button> {}
interface ModalTitleProps extends ComponentProps<
    typeof DialogPrimitive.Title
> {}
interface ModalDescriptionProps extends ComponentProps<
    typeof DialogPrimitive.Description
> {}

const ModalRoot: FC<ModalRootProps> = ({
    children,
    open,
    onOpenChange,
    modal = true,
}) => {
    const isDesktopViewport = useIsDesktopViewport();

    return (
        <Dialog open={open} onOpenChange={onOpenChange} modal={modal}>
            <ModalRootContext.Provider
                value={{
                    open,
                    isDesktopViewport,
                }}
            >
                {children}
            </ModalRootContext.Provider>
        </Dialog>
    );
};

const ModalOverlay: FC<ModalOverlayProps> = ({ className, ...props }) => {
    const { open } = useModalRootContext("Modal.Overlay");

    return (
        <AnimatePresence>
            {open ? (
                <DialogPortal forceMount>
                    <DialogPrimitive.Overlay asChild forceMount>
                        <motion.div
                            data-slot="modal-overlay"
                            className={cn(
                                "fixed inset-0 z-50 bg-black/50 backdrop-blur-sm",
                                className,
                            )}
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            transition={{ duration: 0.18, ease: "easeOut" }}
                            {...props}
                        />
                    </DialogPrimitive.Overlay>
                </DialogPortal>
            ) : null}
        </AnimatePresence>
    );
};

const ModalPositioner: FC<ModalPositionerProps> = ({
    className,
    children,
    ...props
}) => {
    const { open, isDesktopViewport } = useModalRootContext("Modal.Positioner");

    return (
        <AnimatePresence>
            {open ? (
                <DialogPortal forceMount>
                    <div
                        data-slot="modal-positioner"
                        className="pointer-events-none fixed inset-x-0 bottom-0 z-50 flex items-end outline-none md:inset-0 md:items-center md:justify-center"
                    >
                        <DialogPrimitive.Content
                            asChild
                            forceMount
                            onCloseAutoFocus={(event) => {
                                event.preventDefault();
                            }}
                        >
                            <motion.div
                                data-slot="modal-shell"
                                className={cn(
                                    "pointer-events-none w-full md:w-auto",
                                    className,
                                )}
                                initial={
                                    isDesktopViewport
                                        ? { opacity: 0, y: 12, scale: 0.985 }
                                        : { y: "100%" }
                                }
                                animate={
                                    isDesktopViewport
                                        ? { opacity: 1, y: 0, scale: 1 }
                                        : { y: 0 }
                                }
                                exit={
                                    isDesktopViewport
                                        ? { opacity: 0, y: 12, scale: 0.985 }
                                        : { y: "100%" }
                                }
                                transition={
                                    isDesktopViewport
                                        ? { duration: 0.18, ease: "easeOut" }
                                        : {
                                              type: "spring",
                                              stiffness: 420,
                                              damping: 36,
                                              mass: 0.85,
                                          }
                                }
                                {...props}
                            >
                                {children}
                            </motion.div>
                        </DialogPrimitive.Content>
                    </div>
                </DialogPortal>
            ) : null}
        </AnimatePresence>
    );
};

const ModalContent: FC<ModalContentProps> = ({ className, ...props }) => {
    return (
        <div
            data-slot="modal-content"
            className={cn(
                "bg-background pointer-events-auto flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-4xl border-x border-t border-b-0 shadow-lg",
                "md:max-h-[85dvh] md:w-[min(100%-2rem,34rem)] md:rounded-2xl md:border md:border-b",
                className,
            )}
            {...props}
        />
    );
};

const ModalHandle: FC<ModalHandleProps> = ({
    className,
    children,
    ...props
}) => {
    return (
        <div
            data-slot="modal-handle-row"
            className={cn(
                "relative flex items-center justify-center px-4 pt-3 pb-2 md:hidden",
                className,
            )}
            {...props}
        >
            {children ?? (
                <div
                    data-slot="modal-handle"
                    className="bg-muted-foreground/35 pointer-events-none absolute top-1.5 left-1/2 h-1.5 w-10 -translate-x-1/2 rounded-full"
                />
            )}
        </div>
    );
};

const ModalHeader: FC<ModalHeaderProps> = ({ className, ...props }) => {
    return (
        <div
            data-slot="modal-header"
            className={cn(
                "grid grid-cols-[auto_1fr_auto] items-start gap-2 px-4 pt-4 pb-3 md:px-6 md:pt-4",
                className,
            )}
            {...props}
        />
    );
};

const ModalHeaderLeft: FC<ModalHeaderLeftProps> = ({ className, ...props }) => {
    return (
        <div
            data-slot="modal-header-left"
            className={cn(
                "flex min-h-9 min-w-9 items-center justify-start",
                className,
            )}
            {...props}
        />
    );
};

const ModalHeaderCenter: FC<ModalHeaderCenterProps> = ({
    className,
    ...props
}) => {
    return (
        <div
            data-slot="modal-header-center"
            className={cn(
                "flex min-w-0 flex-col items-center gap-1 pt-0.5 text-center md:items-start md:text-left",
                className,
            )}
            {...props}
        />
    );
};

const ModalHeaderRight: FC<ModalHeaderRightProps> = ({
    className,
    ...props
}) => {
    return (
        <div
            data-slot="modal-header-right"
            className={cn(
                "flex min-h-9 min-w-9 items-center justify-end",
                className,
            )}
            {...props}
        />
    );
};

const ModalTitle: FC<ModalTitleProps> = ({ className, ...props }) => {
    return (
        <DialogTitle
            className={cn("text-base md:text-lg", className)}
            {...props}
        />
    );
};

const ModalDescription: FC<ModalDescriptionProps> = ({
    className,
    ...props
}) => {
    return (
        <DialogDescription className={cn("text-sm", className)} {...props} />
    );
};

const ModalBody: FC<ModalBodyProps> = ({ className, ...props }) => {
    return (
        <div
            data-slot="modal-body"
            className={cn(
                "min-h-0 flex-1 overflow-y-auto px-4 pb-4 md:px-6 md:pb-6",
                className,
            )}
            {...props}
        />
    );
};

const ModalFooter: FC<ModalFooterProps> = ({ className, ...props }) => {
    return (
        <div
            data-slot="modal-footer"
            className={cn(
                "border-border/80 shrink-0 border-t px-4 py-3 md:px-6 md:py-4",
                className,
            )}
            {...props}
        />
    );
};

const ModalSafeAreaContent: FC<ModalSafeAreaContentProps> = ({
    className,
    ...props
}) => {
    return (
        <div
            data-slot="modal-safe-area-content"
            className={cn("safe-area-inset-bottom", className)}
            {...props}
        />
    );
};

const ModalClose: FC<ComponentProps<typeof DialogPrimitive.Close>> = ({
    ...props
}) => {
    return <DialogClose {...props} />;
};

const ModalCloseButton: FC<ModalCloseButtonProps> = ({
    className,
    children,
    ...props
}) => {
    return (
        <DialogClose
            className={cn(
                "text-muted-foreground hover:text-foreground inline-flex size-9 items-center justify-center rounded-full border border-input bg-background shadow-xs transition-colors outline-none hover:bg-accent",
                className,
            )}
            {...props}
        >
            {children ?? <X className="size-4" />}
            <span className="sr-only">Close</span>
        </DialogClose>
    );
};

const ModalConfirmButton: FC<ModalConfirmButtonProps> = ({
    className,
    children,
    type = "button",
    size = "icon",
    ...props
}) => {
    return (
        <Button
            type={type}
            variant="default"
            size={size}
            className={cn("rounded-full shadow-xs", className)}
            {...props}
        >
            {children ?? <Check />}
        </Button>
    );
};

const Modal = {
    Root: ModalRoot,
    Overlay: ModalOverlay,
    Positioner: ModalPositioner,
    Content: ModalContent,
    Handle: ModalHandle,
    Header: ModalHeader,
    HeaderLeft: ModalHeaderLeft,
    HeaderCenter: ModalHeaderCenter,
    HeaderRight: ModalHeaderRight,
    Title: ModalTitle,
    Description: ModalDescription,
    Body: ModalBody,
    Footer: ModalFooter,
    SafeAreaContent: ModalSafeAreaContent,
    Close: ModalClose,
    CloseButton: ModalCloseButton,
    ConfirmButton: ModalConfirmButton,
} as const;

export { Modal };
export type {
    ModalRootProps,
    ModalOverlayProps,
    ModalPositionerProps,
    ModalContentProps,
    ModalHandleProps,
    ModalHeaderProps,
    ModalHeaderLeftProps,
    ModalHeaderCenterProps,
    ModalHeaderRightProps,
    ModalTitleProps,
    ModalDescriptionProps,
    ModalBodyProps,
    ModalFooterProps,
    ModalSafeAreaContentProps,
    ModalCloseButtonProps,
    ModalConfirmButtonProps,
};
