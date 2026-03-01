import type { FC } from "react";
import { Banknote } from "lucide-react";
import {
    mapOverviewActivityCopy,
} from "@/routes/home/components/OverviewScreenTabs/activities/activityCopyMapper";
import {
    useOverviewActivityTimeFormatter,
} from "@/routes/home/components/OverviewScreenTabs/activities/activityTime";
import { ActivitiesItem } from "@/routes/home/components/OverviewScreenTabs/activities/components/ActivitiesItem";
import type {
    InvoiceActivityTarget,
    OverviewActivityEntry,
} from "@/routes/home/components/OverviewScreenTabs/activities/types";

interface InvoiceActivityItemProps {
    action: Pick<OverviewActivityEntry, "created_at" | "trigger">;
    target: InvoiceActivityTarget;
    onArchivePress?: () => void;
    onSnoozePress?: () => void;
    onPress?: () => void;
}

const InvoiceActivityItem: FC<InvoiceActivityItemProps> = ({
    action,
    target,
    onArchivePress,
    onSnoozePress,
    onPress,
}) => {
    const { formatDuration, isOverdue } = useOverviewActivityTimeFormatter();
    const copy = mapOverviewActivityCopy({
        action,
        target,
        formatDuration,
        isOverdue,
    });

    return (
        <ActivitiesItem
            icon={Banknote}
            iconClassName="text-emerald-600 dark:text-emerald-300"
            iconBackgroundClassName="bg-emerald-100 dark:bg-emerald-500/20"
            title={copy.title}
            body={copy.body}
            timestamp={copy.timestamp}
            onPress={onPress}
            onArchivePress={onArchivePress}
            onSnoozePress={onSnoozePress}
        />
    );
};

export type { InvoiceActivityItemProps };
export { InvoiceActivityItem };
