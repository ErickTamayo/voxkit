import type { FC } from "react";
import { BriefcaseBusiness } from "lucide-react";
import {
    mapOverviewActivityCopy,
} from "@/routes/home/components/OverviewScreenTabs/activities/activityCopyMapper";
import {
    useOverviewActivityTimeFormatter,
} from "@/routes/home/components/OverviewScreenTabs/activities/activityTime";
import { ActivitiesItem } from "@/routes/home/components/OverviewScreenTabs/activities/components/ActivitiesItem";
import type {
    JobActivityTarget,
    OverviewActivityEntry,
} from "@/routes/home/components/OverviewScreenTabs/activities/types";

interface JobActivityItemProps {
    action: Pick<OverviewActivityEntry, "created_at" | "trigger">;
    target: JobActivityTarget;
    onArchivePress?: () => void;
    onSnoozePress?: () => void;
    onPress?: () => void;
}

const JobActivityItem: FC<JobActivityItemProps> = ({
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
            icon={BriefcaseBusiness}
            iconClassName="text-primary"
            iconBackgroundClassName="bg-primary/10 dark:bg-primary/20"
            title={copy.title}
            body={copy.body}
            timestamp={copy.timestamp}
            onPress={onPress}
            onArchivePress={onArchivePress}
            onSnoozePress={onSnoozePress}
        />
    );
};

export type { JobActivityItemProps };
export { JobActivityItem };
