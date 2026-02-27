import { ActivityTrigger, AuditionStatus } from "@/graphql/types";
import type {
    OverviewActivitiesSectionQuery,
} from "@/routes/home/components/OverviewScreenTabs/activities/ActivitiesScreen.graphql.ts";

type CopyValue = number | string;
type CopyValues = Record<string, CopyValue>;

interface ActivityCopyToken {
    key: string;
    values?: CopyValues;
}

interface ActivityCopyModel {
    body: ActivityCopyToken;
    timestamp: ActivityCopyToken;
    title: ActivityCopyToken;
}

interface ActivityCopyMapperInput {
    action: Pick<OverviewActivityEntry, "created_at" | "trigger">;
    formatDuration: (timestamp: number) => string;
    isOverdue: (timestamp: number) => boolean;
    target: OverviewActivityTarget;
}

type OverviewActivityEntry = OverviewActivitiesSectionQuery["activities"]["data"][number];
type OverviewActivityTarget = NonNullable<OverviewActivityEntry["targetable"]>;
type AuditionTarget = Extract<OverviewActivityTarget, { __typename: "Audition" }>;
type JobTarget = Extract<OverviewActivityTarget, { __typename: "Job" }>;
type InvoiceTarget = Extract<OverviewActivityTarget, { __typename: "Invoice" }>;
type UsageRightTarget = Extract<OverviewActivityTarget, { __typename: "UsageRight" }>;

const AUDITION_STATUS_LABELS: Record<AuditionStatus, string> = {
    [AuditionStatus.Callback]: "Callback",
    [AuditionStatus.Expired]: "Expired",
    [AuditionStatus.Lost]: "Lost",
    [AuditionStatus.Preparing]: "Preparing",
    [AuditionStatus.Received]: "Received",
    [AuditionStatus.Shortlisted]: "Shortlisted",
    [AuditionStatus.Submitted]: "Submitted",
    [AuditionStatus.Won]: "Won",
};

function toTrimmedLabel(value: string | null | undefined, fallback: string): string {
    const trimmed = value?.trim();

    return trimmed === undefined || trimmed.length === 0 ? fallback : trimmed;
}

function getActivityTimestampCopy(
    createdAt: number,
    formatDuration: (timestamp: number) => string,
): ActivityCopyToken {
    return {
        key: "Added {{duration}} ago",
        values: {
            duration: formatDuration(createdAt),
        },
    };
}

function mapAuditionCopy(input: Omit<ActivityCopyMapperInput, "target"> & { target: AuditionTarget }): ActivityCopyModel {
    const project = toTrimmedLabel(input.target.project_title, "Untitled Project");
    const source = toTrimmedLabel(input.target.sourceable?.name, "Unknown source");
    const subject = toTrimmedLabel(input.target.sourceable?.name, project);
    const status = AUDITION_STATUS_LABELS[input.target.status] ?? "Unknown Status";
    const deadline = input.target.response_deadline ?? null;
    const duration = deadline === null ? null : input.formatDuration(deadline);
    const overdue = deadline === null ? false : input.isOverdue(deadline);

    const titleKey = input.action.trigger === ActivityTrigger.AuditionResponseDue
        ? overdue
            ? "inbox.audition.overdue.title"
            : "inbox.audition.responseDue.title"
        : "inbox.audition.update.title";

    let body: ActivityCopyToken;

    if (duration === null) {
        body = {
            key: "inbox.audition.deadlineMissing",
            values: { project, source },
        };
    } else if (overdue) {
        body = {
            key: "inbox.audition.overdue.body",
            values: { project, source, duration, status },
        };
    } else {
        body = {
            key: "inbox.audition.responseDue.body",
            values: { project, source, duration },
        };
    }

    return {
        title: {
            key: titleKey,
            values: { subject },
        },
        body,
        timestamp: getActivityTimestampCopy(input.action.created_at, input.formatDuration),
    };
}

function mapJobCopy(input: Omit<ActivityCopyMapperInput, "target"> & { target: JobTarget }): ActivityCopyModel {
    const project = toTrimmedLabel(input.target.project_title, "Untitled Project");
    const createdDuration = input.formatDuration(input.action.created_at);
    const timestamp = getActivityTimestampCopy(input.action.created_at, input.formatDuration);

    if (input.action.trigger === ActivityTrigger.JobSessionUpcoming) {
        const session = input.target.session_date ?? null;
        const duration = session === null ? null : input.formatDuration(session);
        const sessionPast = session === null ? false : input.isOverdue(session);

        return {
            title: {
                key: "inbox.job.sessionUpcoming.title",
                values: { project },
            },
            body: duration === null
                ? {
                    key: "inbox.job.sessionMissing",
                    values: { project },
                }
                : sessionPast
                    ? {
                        key: "inbox.job.sessionPast.body",
                        values: { project, duration },
                    }
                    : {
                        key: "inbox.job.sessionUpcoming.body",
                        values: { project, duration },
                    },
            timestamp,
        };
    }

    if (input.action.trigger === ActivityTrigger.JobDeliveryDue) {
        const delivery = input.target.delivery_deadline ?? null;
        const duration = delivery === null ? null : input.formatDuration(delivery);
        const deliveryOverdue = delivery === null ? false : input.isOverdue(delivery);

        return {
            title: {
                key: deliveryOverdue
                    ? "inbox.job.deliveryOverdue.title"
                    : "inbox.job.deliveryDue.title",
                values: { project },
            },
            body: duration === null
                ? {
                    key: "inbox.job.deliveryMissing",
                    values: { project },
                }
                : deliveryOverdue
                    ? {
                        key: "inbox.job.deliveryOverdue.body",
                        values: { project, duration },
                    }
                    : {
                        key: "inbox.job.deliveryDue.body",
                        values: { project, duration },
                    },
            timestamp,
        };
    }

    if (input.action.trigger === ActivityTrigger.JobRevisionRequested) {
        return {
            title: {
                key: "inbox.job.revisionRequested.title",
                values: { project },
            },
            body: {
                key: "inbox.job.revisionRequested.body",
                values: { project, duration: createdDuration },
            },
            timestamp,
        };
    }

    return {
        title: {
            key: "inbox.job.update.title",
            values: { project },
        },
        body: {
            key: "inbox.job.update.body",
            values: { project },
        },
        timestamp,
    };
}

function mapInvoiceCopy(input: Omit<ActivityCopyMapperInput, "target"> & { target: InvoiceTarget }): ActivityCopyModel {
    const project = toTrimmedLabel(input.target.job?.project_title, "Untitled Job");
    const createdDuration = input.formatDuration(input.action.created_at);
    const amountCents = input.target.total.original.amount_cents;
    const currency = input.target.total.original.currency;
    const amount = `${currency} ${(amountCents / 100).toFixed(2)}`;

    if (input.action.trigger === ActivityTrigger.InvoiceDueSoon) {
        return {
            title: {
                key: "inbox.invoice.dueSoon.title",
                values: { project },
            },
            body: {
                key: "inbox.invoice.dueSoon.body",
                values: { project, amount, duration: createdDuration },
            },
            timestamp: getActivityTimestampCopy(input.action.created_at, input.formatDuration),
        };
    }

    if (input.action.trigger === ActivityTrigger.InvoiceOverdue) {
        return {
            title: {
                key: "inbox.invoice.overdue.title",
                values: { project },
            },
            body: {
                key: "inbox.invoice.overdue.body",
                values: { project, amount, duration: createdDuration },
            },
            timestamp: getActivityTimestampCopy(input.action.created_at, input.formatDuration),
        };
    }

    return {
        title: {
            key: "inbox.invoice.update.title",
            values: { project },
        },
        body: {
            key: "inbox.invoice.update.body",
            values: { project, amount },
        },
        timestamp: getActivityTimestampCopy(input.action.created_at, input.formatDuration),
    };
}

function mapUsageRightCopy(input: Omit<ActivityCopyMapperInput, "target"> & { target: UsageRightTarget }): ActivityCopyModel {
    const project = toTrimmedLabel(input.target.usable?.project_title, "Untitled Project");
    const expiration = input.target.expiration_date ?? null;
    const duration = expiration === null ? null : input.formatDuration(expiration);
    const expired = expiration === null ? false : input.isOverdue(expiration);

    if (input.action.trigger === ActivityTrigger.UsageRightsExpiring) {
        return {
            title: {
                key: expired
                    ? "inbox.usageRights.expired.title"
                    : "inbox.usageRights.expiring.title",
                values: { project },
            },
            body: duration === null
                ? {
                    key: "inbox.usageRights.noExpiration",
                    values: { project },
                }
                : expired
                    ? {
                        key: "inbox.usageRights.expired.body",
                        values: { project, duration },
                    }
                    : {
                        key: "inbox.usageRights.expiring.body",
                        values: { project, duration },
                    },
            timestamp: getActivityTimestampCopy(input.action.created_at, input.formatDuration),
        };
    }

    return {
        title: {
            key: "inbox.usageRights.update.title",
            values: { project },
        },
        body: {
            key: "inbox.usageRights.update.body",
            values: { project },
        },
        timestamp: getActivityTimestampCopy(input.action.created_at, input.formatDuration),
    };
}

function mapOverviewActivityCopy(input: ActivityCopyMapperInput): ActivityCopyModel {
    switch (input.target.__typename) {
        case "Audition":
            return mapAuditionCopy({
                ...input,
                target: input.target,
            });
        case "Job":
            return mapJobCopy({
                ...input,
                target: input.target,
            });
        case "Invoice":
            return mapInvoiceCopy({
                ...input,
                target: input.target,
            });
        case "UsageRight":
            return mapUsageRightCopy({
                ...input,
                target: input.target,
            });
    }
}

export {
    mapOverviewActivityCopy,
};
export type {
    ActivityCopyMapperInput,
    ActivityCopyModel,
    ActivityCopyToken,
    OverviewActivityEntry,
    OverviewActivityTarget,
};
