import { describe, expect, it } from "vitest";
import { ActivityTrigger, AuditionStatus } from "@/graphql/types";
import {
    mapOverviewActivityCopy,
    type ActivityCopyMapperInput,
} from "@/routes/home/components/OverviewScreenTabs/activities/activityCopyMapper";

function createMapperInput(
    overrides: Partial<ActivityCopyMapperInput>,
): ActivityCopyMapperInput {
    return {
        action: {
            created_at: 50,
            trigger: ActivityTrigger.AuditionResponseDue,
        },
        formatDuration: (timestamp) => `${timestamp}d`,
        isOverdue: (timestamp) => timestamp < 100,
        target: {
            __typename: "Audition",
            id: "aud-1",
            project_title: "Project A",
            project_deadline: null,
            response_deadline: 20,
            status: AuditionStatus.Callback,
            sourceable: {
                __typename: "Platform",
                id: "source-1",
                name: "Agency X",
            },
        },
        ...overrides,
    };
}

describe("activityCopyMapper", () => {
    it("maps overdue audition activity copy", () => {
        const copy = mapOverviewActivityCopy(createMapperInput({}));

        expect(copy.title).toStrictEqual({
            key: "inbox.audition.overdue.title",
            values: { subject: "Agency X" },
        });
        expect(copy.body).toStrictEqual({
            key: "inbox.audition.overdue.body",
            values: {
                project: "Project A",
                source: "Agency X",
                duration: "20d",
                status: "Callback",
            },
        });
        expect(copy.timestamp).toStrictEqual({
            key: "Added {{duration}} ago",
            values: { duration: "50d" },
        });
    });

    it("maps missing job session copy fallback", () => {
        const copy = mapOverviewActivityCopy(createMapperInput({
            action: {
                created_at: 70,
                trigger: ActivityTrigger.JobSessionUpcoming,
            },
            target: {
                __typename: "Job",
                id: "job-1",
                project_title: "Narration",
                session_date: null,
                delivery_deadline: null,
            },
        }));

        expect(copy.title.key).toBe("inbox.job.sessionUpcoming.title");
        expect(copy.body).toStrictEqual({
            key: "inbox.job.sessionMissing",
            values: { project: "Narration" },
        });
    });

    it("maps invoice amount and overdue copy", () => {
        const copy = mapOverviewActivityCopy(createMapperInput({
            action: {
                created_at: 42,
                trigger: ActivityTrigger.InvoiceOverdue,
            },
            target: {
                __typename: "Invoice",
                id: "inv-1",
                job: {
                    __typename: "Job",
                    project_title: "Campaign",
                },
                total: {
                    __typename: "MonetaryAmount",
                    original: {
                        __typename: "Money",
                        currency: "USD",
                        amount_cents: 12345,
                    },
                },
            },
        }));

        expect(copy.title.key).toBe("inbox.invoice.overdue.title");
        expect(copy.body).toStrictEqual({
            key: "inbox.invoice.overdue.body",
            values: {
                project: "Campaign",
                amount: "USD 123.45",
                duration: "42d",
            },
        });
    });

    it("maps usage rights fallback when expiration is missing", () => {
        const copy = mapOverviewActivityCopy(createMapperInput({
            action: {
                created_at: 75,
                trigger: ActivityTrigger.UsageRightsExpiring,
            },
            target: {
                __typename: "UsageRight",
                id: "usage-1",
                expiration_date: null,
                usable: {
                    __typename: "Audition",
                    id: "aud-7",
                    project_title: "Brand Spot",
                },
            },
        }));

        expect(copy.title).toStrictEqual({
            key: "inbox.usageRights.expiring.title",
            values: { project: "Brand Spot" },
        });
        expect(copy.body).toStrictEqual({
            key: "inbox.usageRights.noExpiration",
            values: { project: "Brand Spot" },
        });
    });
});
