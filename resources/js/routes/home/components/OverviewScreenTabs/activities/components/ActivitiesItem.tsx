import type { FC } from "react";
import { Trans, useTranslation } from "react-i18next";
import type { LucideIcon } from "lucide-react";
import { Archive } from "lucide-react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import type { ActivityCopyModel } from "@/routes/home/components/OverviewScreenTabs/activities/activityCopyMapper";

interface ActivityCopyToken {
    key: string;
    values?: Record<string, number | string>;
}

interface ActivitiesItemProps {
    body: ActivityCopyToken;
    timestamp: ActivityCopyToken;
    title: ActivityCopyToken;
    icon: LucideIcon;
    iconBackgroundClassName: string;
    iconClassName: string;
    onArchivePress?: () => void;
    onPress?: () => void;
}

const ActivitiesItem: FC<ActivitiesItemProps> = ({
    body,
    timestamp,
    title,
    icon: Icon,
    iconBackgroundClassName,
    iconClassName,
    onArchivePress,
    onPress,
}) => {
    const { t } = useTranslation();

    const header = (
        <div className="flex items-start gap-3">
            <div
                className={cn(
                    "mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full",
                    iconBackgroundClassName,
                )}
            >
                <Icon className={cn("size-5", iconClassName)} strokeWidth={1.5} />
            </div>

            <div className="min-w-0 flex-1 space-y-1">
                <div className="flex items-start justify-between gap-2">
                    <h3 className="min-w-0 flex-1 text-base leading-6 font-semibold text-foreground">
                        <Trans
                            i18nKey={title.key}
                            values={title.values}
                            components={{
                                bold: <span className="font-semibold text-foreground" />,
                            }}
                        />
                    </h3>
                    <p className="pt-0.5 text-xs leading-5 font-medium whitespace-nowrap text-muted-foreground">
                        {t(timestamp.key, timestamp.values)}
                    </p>
                </div>

                <p className="text-sm leading-6 text-muted-foreground">
                    <Trans
                        i18nKey={body.key}
                        values={body.values}
                        components={{
                            bold: <span className="font-semibold text-muted-foreground" />,
                        }}
                    />
                </p>
            </div>
        </div>
    );

    return (
        <article className="space-y-2 border-b border-border/70 px-4 py-4 last:border-b-0">
            {onPress !== undefined ? (
                <button
                    type="button"
                    className="w-full text-left outline-none"
                    onClick={onPress}
                >
                    {header}
                </button>
            ) : (
                header
            )}

            {onArchivePress !== undefined ? (
                <div className="pl-10">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="text-muted-foreground hover:text-foreground"
                        onClick={onArchivePress}
                    >
                        <Archive className="size-4" />
                        {t("Archive")}
                    </Button>
                </div>
            ) : null}
        </article>
    );
};

type ActivitiesItemCopy = Pick<ActivityCopyModel, "body" | "timestamp" | "title">;

export type { ActivitiesItemCopy, ActivitiesItemProps };
export { ActivitiesItem };
