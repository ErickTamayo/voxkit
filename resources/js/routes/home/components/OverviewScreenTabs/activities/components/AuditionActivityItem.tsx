import type { FC } from "react";
import { MicVocal } from "lucide-react";
import {
    mapOverviewActivityCopy,
} from "@/routes/home/components/OverviewScreenTabs/activities/activityCopyMapper";
import {
    useOverviewActivityTimeFormatter,
} from "@/routes/home/components/OverviewScreenTabs/activities/activityTime";
import { ActivitiesItem } from "@/routes/home/components/OverviewScreenTabs/activities/components/ActivitiesItem";
import type {
    AuditionActivityTarget,
    OverviewActivityEntry,
} from "@/routes/home/components/OverviewScreenTabs/activities/types";

interface AuditionActivityItemProps {
    action: Pick<OverviewActivityEntry, "created_at" | "trigger">;
    target: AuditionActivityTarget;
    onArchivePress?: () => void;
    onPress?: () => void;
}

const AuditionActivityItem: FC<AuditionActivityItemProps> = ({
    action,
    target,
    onArchivePress,
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
            icon={MicVocal}
            iconClassName="text-amber-600 dark:text-amber-300"
            iconBackgroundClassName="bg-amber-100 dark:bg-amber-500/20"
            title={copy.title}
            body={copy.body}
            timestamp={copy.timestamp}
            onPress={onPress}
            onArchivePress={onArchivePress}
        />
    );
};

export type { AuditionActivityItemProps };
export { AuditionActivityItem };
