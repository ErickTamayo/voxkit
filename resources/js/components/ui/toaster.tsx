import type { FC } from "react";
import { Toaster as SonnerToaster } from "sonner";

interface ToasterProps {
    position?: "bottom-center" | "bottom-left" | "bottom-right" | "top-center" | "top-left" | "top-right";
}

const TOAST_SAFE_AREA_OFFSET = {
    top: "calc(var(--safe-area-top) + 0.5rem)",
    right: "0.5rem",
    bottom: "calc(var(--safe-area-bottom) + 0.5rem)",
    left: "0.5rem",
} as const;

const Toaster: FC<ToasterProps> = ({
    position = "top-center",
}) => {
    return (
        <SonnerToaster
            position={position}
            closeButton
            richColors
            offset={TOAST_SAFE_AREA_OFFSET}
            toastOptions={{
                classNames: {
                    toast: "rounded-lg",
                    title: "text-sm font-medium",
                    description: "text-sm",
                },
            }}
        />
    );
};

export { Toaster };
