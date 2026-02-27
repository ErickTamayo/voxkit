import type { FC } from "react";
import type { LucideIcon } from "lucide-react";
import { cn } from "@/lib/utils";

interface ActivityDetailRowProps {
    label: string;
    value: string;
    icon?: LucideIcon;
    iconSize?: number;
    size?: "sm" | "xs";
    valueClassName?: string;
}

const ActivityDetailRow: FC<ActivityDetailRowProps> = ({
    label,
    value,
    icon: Icon,
    iconSize = 13,
    size = "sm",
    valueClassName,
}) => {
    const textSizeClassName = size === "xs" ? "text-xs" : "text-sm";

    return (
        <div className="flex items-center justify-between gap-2">
            <div className="flex min-w-0 items-center gap-1.5">
                {Icon !== undefined ? (
                    <Icon
                        className="shrink-0 text-muted-foreground"
                        size={iconSize}
                        strokeWidth={2}
                    />
                ) : null}
                <span className={cn("font-medium text-muted-foreground", textSizeClassName)}>
                    {label}
                </span>
            </div>

            <span
                className={cn(
                    "text-right font-medium tracking-wide text-foreground",
                    textSizeClassName,
                    valueClassName,
                )}
            >
                {value}
            </span>
        </div>
    );
};

export type { ActivityDetailRowProps };
export { ActivityDetailRow };
