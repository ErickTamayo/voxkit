import type { FC } from "react";
import { Toaster as SonnerToaster } from "sonner";

interface ToasterProps {
    position?: "bottom-center" | "bottom-left" | "bottom-right" | "top-center" | "top-left" | "top-right";
}

const TOAST_SAFE_AREA_OFFSET = {
    top: "calc(var(--safe-area-top) + 1rem)",
    right: "1rem",
    bottom: "calc(var(--safe-area-bottom) + 1rem)",
    left: "1rem",
} as const;

const Toaster: FC<ToasterProps> = ({
    position = "bottom-center",
}) => {
    return (
        <SonnerToaster
            position={position}
            className="toaster group"
            gap={8}
            closeButton
            richColors={false}
            offset={TOAST_SAFE_AREA_OFFSET}
            mobileOffset={TOAST_SAFE_AREA_OFFSET}
            toastOptions={{
                classNames: {
                    toast: [
                        "group toast group-[.toaster]:pointer-events-auto",
                        "group-[.toaster]:w-[calc(100vw-2rem)] group-[.toaster]:max-w-md",
                        "rounded-xl border border-border bg-card p-4 text-foreground",
                        "shadow-[0_2px_8px_rgba(10,13,18,0.12)]",
                        "dark:shadow-[0_2px_8px_rgba(0,0,0,0.35)]",
                    ].join(" "),
                    content: "gap-1",
                    title: "text-sm font-semibold leading-5 text-[inherit]",
                    description: "text-xs leading-[1.125rem] text-[inherit] opacity-80",
                    closeButton: [
                        "rounded-md border border-transparent p-1",
                        "text-muted-foreground/80 transition-colors",
                        "hover:bg-accent hover:text-foreground",
                        "focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none",
                    ].join(" "),
                    success: [
                        "border-[#ABEFC6] bg-[#ECFDF3] text-[#067647]",
                        "dark:border-[#067647] dark:bg-[#052e1a] dark:text-[#6ce9a6]",
                    ].join(" "),
                    error: [
                        "border-[#FDA29B] bg-[#FEF3F2] text-[#D92D20]",
                        "dark:border-[#F04438] dark:bg-[#450A0A] dark:text-[#FDA29B]",
                    ].join(" "),
                },
            }}
        />
    );
};

export { Toaster };
