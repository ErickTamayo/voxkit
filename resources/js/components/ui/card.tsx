import type { ComponentProps, FC } from "react";
import { cn } from "@/lib/utils";

interface CardProps extends ComponentProps<"div"> {}

const Card: FC<CardProps> = ({
    className,
    ...props
}) => {
    return (
        <div
            data-slot="card"
            className={cn(
                "bg-card text-card-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-sm",
                className,
            )}
            {...props}
        />
    );
};

interface CardHeaderProps extends ComponentProps<"div"> {}

const CardHeader: FC<CardHeaderProps> = ({
    className,
    ...props
}) => {
    return (
        <div
            data-slot="card-header"
            className={cn(
                "@container/card-header grid auto-rows-min grid-rows-[auto_auto] items-start gap-2 px-6 has-data-[slot=card-action]:grid-cols-[1fr_auto] [.border-b]:pb-6",
                className,
            )}
            {...props}
        />
    );
};

interface CardTitleProps extends ComponentProps<"div"> {}

const CardTitle: FC<CardTitleProps> = ({
    className,
    ...props
}) => {
    return (
        <div
            data-slot="card-title"
            className={cn("leading-none font-semibold", className)}
            {...props}
        />
    );
};

interface CardDescriptionProps extends ComponentProps<"div"> {}

const CardDescription: FC<CardDescriptionProps> = ({
    className,
    ...props
}) => {
    return (
        <div
            data-slot="card-description"
            className={cn("text-muted-foreground text-sm", className)}
            {...props}
        />
    );
};

interface CardActionProps extends ComponentProps<"div"> {}

const CardAction: FC<CardActionProps> = ({
    className,
    ...props
}) => {
    return (
        <div
            data-slot="card-action"
            className={cn(
                "col-start-2 row-span-2 row-start-1 self-start justify-self-end",
                className,
            )}
            {...props}
        />
    );
};

interface CardContentProps extends ComponentProps<"div"> {}

const CardContent: FC<CardContentProps> = ({
    className,
    ...props
}) => {
    return (
        <div data-slot="card-content" className={cn("px-6", className)} {...props} />
    );
};

interface CardFooterProps extends ComponentProps<"div"> {}

const CardFooter: FC<CardFooterProps> = ({
    className,
    ...props
}) => {
    return (
        <div
            data-slot="card-footer"
            className={cn("flex items-center px-6 [.border-t]:pt-6", className)}
            {...props}
        />
    );
};

export {
    Card,
    CardHeader,
    CardFooter,
    CardTitle,
    CardAction,
    CardDescription,
    CardContent,
};
