import { useMutation, useSuspenseQuery } from "@apollo/client/react";
import type { FC } from "react";
import { useTranslation } from "react-i18next";
import { toast } from "sonner";
import {
    ActivityAction,
    QueryActivitiesWhereColumn,
    SqlOperator,
} from "@/graphql/types";
import {
    OverviewActivitiesTabBadgeDocument,
} from "@/routes/home/components/OverviewScreenTabs/activities/ActivitiesTabBadge.graphql.ts";
import {
    OverviewActivitiesSectionDocument,
    OverviewArchiveActivityDocument,
} from "@/routes/home/components/OverviewScreenTabs/activities/ActivitiesScreen.graphql.ts";
import {
    AuditionActivityItem,
} from "@/routes/home/components/OverviewScreenTabs/activities/components/AuditionActivityItem";
import {
    InvoiceActivityItem,
} from "@/routes/home/components/OverviewScreenTabs/activities/components/InvoiceActivityItem";
import {
    JobActivityItem,
} from "@/routes/home/components/OverviewScreenTabs/activities/components/JobActivityItem";
import {
    UsageRightActivityItem,
} from "@/routes/home/components/OverviewScreenTabs/activities/components/UsageRightActivityItem";
import type { OverviewActivityEntry } from "@/routes/home/components/OverviewScreenTabs/activities/types";

const OPEN_ACTIVITIES_WHERE = {
    column: QueryActivitiesWhereColumn.Action,
    operator: SqlOperator.IsNull,
};

type RenderableActivity = OverviewActivityEntry & {
    targetable: NonNullable<OverviewActivityEntry["targetable"]>;
};

function hasTargetable(activity: OverviewActivityEntry): activity is RenderableActivity {
    return activity.targetable !== null && activity.targetable !== undefined;
}

const ActivitiesScreen: FC = () => {
    const { t } = useTranslation();
    const { data } = useSuspenseQuery(OverviewActivitiesSectionDocument, {
        variables: {
            where: OPEN_ACTIVITIES_WHERE,
        },
    });
    const [archiveActivity] = useMutation(OverviewArchiveActivityDocument, {
        refetchQueries: [
            {
                query: OverviewActivitiesSectionDocument,
                variables: {
                    where: OPEN_ACTIVITIES_WHERE,
                },
            },
            {
                query: OverviewActivitiesTabBadgeDocument,
                variables: {
                    where: OPEN_ACTIVITIES_WHERE,
                },
            },
        ],
        awaitRefetchQueries: true,
    });
    const visibleActivities = data.activities.data.filter(hasTargetable);

    const handleArchive = async (activityId: string): Promise<void> => {
        try {
            await archiveActivity({
                variables: {
                    input: {
                        id: activityId,
                    },
                },
                optimisticResponse: {
                    archiveActivity: {
                        __typename: "Activity",
                        id: activityId,
                        action: ActivityAction.Archived,
                    },
                },
            });
            toast.success(t("Item archived"), {
                description: t("The item has been moved to the archive."),
            });
        } catch {
            toast.error(t("Failed to archive item"), {
                description: t("Please try again later."),
            });
        }
    };

    if (visibleActivities.length === 0) {
        return (
            <section className="-mx-4 border-y border-border/70 bg-background px-4 py-10">
                <p className="text-sm text-muted-foreground">
                    {t("No activities right now.")}
                </p>
            </section>
        );
    }

    return (
        <section className="-mx-4 h-full overflow-y-auto border-y border-border/70 bg-background">
            <div className="divide-y divide-border/70">
                {visibleActivities.map((activity) => {
                    return (
                        <ActivityListRow
                            key={activity.id}
                            activity={activity}
                            onArchive={(activityId) => {
                                void handleArchive(activityId);
                            }}
                        />
                    );
                })}
            </div>
        </section>
    );
};

interface ActivityListRowProps {
    activity: RenderableActivity;
    onArchive: (activityId: string) => void;
}

const ActivityListRow: FC<ActivityListRowProps> = ({
    activity,
    onArchive,
}) => {
    const target = activity.targetable;

    switch (target.__typename) {
        case "Audition":
            return (
                <AuditionActivityItem
                    action={activity}
                    target={target}
                    onArchivePress={() => onArchive(activity.id)}
                />
            );
        case "Job":
            return (
                <JobActivityItem
                    action={activity}
                    target={target}
                    onArchivePress={() => onArchive(activity.id)}
                />
            );
        case "Invoice":
            return (
                <InvoiceActivityItem
                    action={activity}
                    target={target}
                    onArchivePress={() => onArchive(activity.id)}
                />
            );
        case "UsageRight":
            return (
                <UsageRightActivityItem
                    action={activity}
                    target={target}
                    onArchivePress={() => onArchive(activity.id)}
                />
            );
    }
};

export { ActivitiesScreen };
