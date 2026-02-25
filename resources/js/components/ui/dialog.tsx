import type { ComponentProps, FC } from "react";
import * as DialogPrimitive from "@radix-ui/react-dialog";
import { X } from "lucide-react";
import { cn } from "@/lib/utils";

type DialogProps = ComponentProps<typeof DialogPrimitive.Root>;
type DialogTriggerProps = ComponentProps<typeof DialogPrimitive.Trigger>;
type DialogPortalProps = ComponentProps<typeof DialogPrimitive.Portal>;
type DialogCloseProps = ComponentProps<typeof DialogPrimitive.Close>;
type DialogOverlayProps = ComponentProps<typeof DialogPrimitive.Overlay>;
type DialogContentPrimitiveProps = ComponentProps<typeof DialogPrimitive.Content>;
type DialogTitleProps = ComponentProps<typeof DialogPrimitive.Title>;
type DialogDescriptionProps = ComponentProps<typeof DialogPrimitive.Description>;

interface DialogContentProps extends DialogContentPrimitiveProps {
    showCloseButton?: boolean;
}

interface DialogHeaderProps extends ComponentProps<"div"> {}
interface DialogFooterProps extends ComponentProps<"div"> {}

const Dialog: FC<DialogProps> = ({ ...props }) => {
    return <DialogPrimitive.Root data-slot="dialog" {...props} />;
};

const DialogTrigger: FC<DialogTriggerProps> = ({ ...props }) => {
    return <DialogPrimitive.Trigger data-slot="dialog-trigger" {...props} />;
};

const DialogPortal: FC<DialogPortalProps> = ({ ...props }) => {
    return <DialogPrimitive.Portal data-slot="dialog-portal" {...props} />;
};

const DialogClose: FC<DialogCloseProps> = ({ ...props }) => {
    return <DialogPrimitive.Close data-slot="dialog-close" {...props} />;
};

const DialogOverlay: FC<DialogOverlayProps> = ({
    className,
    ...props
}) => {
    return (
        <DialogPrimitive.Overlay
            data-slot="dialog-overlay"
            className={cn(
                "data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 fixed inset-0 z-50 bg-black/50 backdrop-blur-sm",
                className,
            )}
            {...props}
        />
    );
};

const DialogContent: FC<DialogContentProps> = ({
    className,
    children,
    showCloseButton = true,
    ...props
}) => {
    return (
        <DialogPortal>
            <DialogOverlay />
            <DialogPrimitive.Content
                data-slot="dialog-content"
                className={cn(
                    "bg-background data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 fixed top-1/2 left-1/2 z-50 grid w-full max-w-[calc(100%-2rem)] -translate-x-1/2 -translate-y-1/2 gap-4 rounded-xl border p-6 shadow-lg duration-200 sm:max-w-lg",
                    className,
                )}
                {...props}
            >
                {children}

                {showCloseButton ? (
                    <DialogPrimitive.Close
                        className={cn(
                            "text-muted-foreground hover:text-foreground focus-visible:border-ring focus-visible:ring-ring/50 absolute top-4 right-4 rounded-sm opacity-70 transition-opacity outline-none focus-visible:ring-[3px] disabled:pointer-events-none",
                            "data-[state=open]:bg-accent data-[state=open]:text-muted-foreground",
                        )}
                    >
                        <X className="size-4" />
                        <span className="sr-only">Close</span>
                    </DialogPrimitive.Close>
                ) : null}
            </DialogPrimitive.Content>
        </DialogPortal>
    );
};

const DialogHeader: FC<DialogHeaderProps> = ({
    className,
    ...props
}) => {
    return (
        <div
            data-slot="dialog-header"
            className={cn("flex flex-col gap-2 text-center sm:text-left", className)}
            {...props}
        />
    );
};

const DialogFooter: FC<DialogFooterProps> = ({
    className,
    ...props
}) => {
    return (
        <div
            data-slot="dialog-footer"
            className={cn(
                "flex flex-col-reverse gap-2 sm:flex-row sm:justify-end",
                className,
            )}
            {...props}
        />
    );
};

const DialogTitle: FC<DialogTitleProps> = ({
    className,
    ...props
}) => {
    return (
        <DialogPrimitive.Title
            data-slot="dialog-title"
            className={cn("text-lg leading-none font-semibold", className)}
            {...props}
        />
    );
};

const DialogDescription: FC<DialogDescriptionProps> = ({
    className,
    ...props
}) => {
    return (
        <DialogPrimitive.Description
            data-slot="dialog-description"
            className={cn("text-muted-foreground text-sm", className)}
            {...props}
        />
    );
};

export {
    Dialog,
    DialogTrigger,
    DialogPortal,
    DialogClose,
    DialogOverlay,
    DialogContent,
    DialogHeader,
    DialogFooter,
    DialogTitle,
    DialogDescription,
};
