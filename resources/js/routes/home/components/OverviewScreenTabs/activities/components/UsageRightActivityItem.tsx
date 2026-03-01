import type { FC } from "react";
import { ShieldCheck } from "lucide-react";
import {
    mapOverviewActivityCopy,
} from "@/routes/home/components/OverviewScreenTabs/activities/activityCopyMapper";
import {
    useOverviewActivityTimeFormatter,
} from "@/routes/home/components/OverviewScreenTabs/activities/activityTime";
import { ActivitiesItem } from "@/routes/home/components/OverviewScreenTabs/activities/components/ActivitiesItem";
import type {
    OverviewActivityEntry,
    UsageRightActivityTarget,
} from "@/routes/home/components/OverviewScreenTabs/activities/types";

interface UsageRightActivityItemProps {
    action: Pick<OverviewActivityEntry, "created_at" | "trigger">;
    target: UsageRightActivityTarget;
    onArchivePress?: () => void;
    onSnoozePress?: () => void;
    onPress?: () => void;
}

const UsageRightActivityItem: FC<UsageRightActivityItemProps> = ({
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
            icon={ShieldCheck}
            iconClassName="text-rose-600 dark:text-rose-300"
            iconBackgroundClassName="bg-rose-100 dark:bg-rose-500/20"
            title={copy.title}
            body={copy.body}
            timestamp={copy.timestamp}
            onPress={onPress}
            onArchivePress={onArchivePress}
            onSnoozePress={onSnoozePress}
        />
    );
};

export type { UsageRightActivityItemProps };
export { UsageRightActivityItem };
