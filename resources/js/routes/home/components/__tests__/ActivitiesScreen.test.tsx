import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import {
    ActivityAction,
    ActivityTrigger,
    AuditionStatus,
} from "@/graphql/types";
import { ActivitiesScreen } from "@/routes/home/components/OverviewScreenTabs/activities/ActivitiesScreen";
import type {
    OverviewActivitiesSectionQuery,
} from "@/routes/home/components/OverviewScreenTabs/activities/ActivitiesScreen.graphql.ts";

const useMutationMock = vi.fn();
const useSuspenseQueryMock = vi.fn();
const toastErrorMock = vi.fn();
const toastSuccessMock = vi.fn();
const openAuditionMock = vi.fn();
const openAgentMock = vi.fn();
const openClientMock = vi.fn();
const openExpenseMock = vi.fn();
const openInvoiceMock = vi.fn();
const openJobMock = vi.fn();
const openNoteMock = vi.fn();
const openPlatformMock = vi.fn();
const openUsageRightMock = vi.fn();
const openMenuDrawerMock = vi.fn();
const openSearchMock = vi.fn();
const openSettingsMock = vi.fn();
const snoozeActivityMock = vi.fn();

vi.mock("@apollo/client/react", () => {
    return {
        useMutation: (...args: unknown[]) => useMutationMock(...args),
        useSuspenseQuery: (...args: unknown[]) => useSuspenseQueryMock(...args),
    };
});

vi.mock("react-i18next", () => {
    return {
        useTranslation: () => ({
            t: (key: string): string => key,
        }),
        Trans: ({ i18nKey }: { i18nKey: string }) => i18nKey,
    };
});

vi.mock("sonner", () => {
    return {
        toast: {
            success: (...args: unknown[]) => toastSuccessMock(...args),
            error: (...args: unknown[]) => toastErrorMock(...args),
        },
    };
});

vi.mock("@/routes/home/components/overviewNavigation", () => {
    return {
        useOverviewNavigation: () => ({
            onOpenAgent: (...args: unknown[]) => openAgentMock(...args),
            onOpenAudition: (...args: unknown[]) => openAuditionMock(...args),
            onOpenClient: (...args: unknown[]) => openClientMock(...args),
            onOpenExpense: (...args: unknown[]) => openExpenseMock(...args),
            onOpenInvoice: (...args: unknown[]) => openInvoiceMock(...args),
            onOpenJob: (...args: unknown[]) => openJobMock(...args),
            onOpenMenuDrawer: (...args: unknown[]) => openMenuDrawerMock(...args),
            onOpenNote: (...args: unknown[]) => openNoteMock(...args),
            onOpenPlatform: (...args: unknown[]) => openPlatformMock(...args),
            onOpenSearch: (...args: unknown[]) => openSearchMock(...args),
            onOpenSettings: (...args: unknown[]) => openSettingsMock(...args),
            onOpenUsageRight: (...args: unknown[]) => openUsageRightMock(...args),
            onSnoozeActivity: (...args: unknown[]) => snoozeActivityMock(...args),
        }),
    };
});

vi.mock(
    "@/routes/home/components/OverviewScreenTabs/activities/components/AuditionActivityItem",
    () => {
        return {
            AuditionActivityItem: ({
                target,
                onPress,
                onArchivePress,
                onSnoozePress,
            }: {
                target: { id: string };
                onPress?: () => void;
                onArchivePress?: () => void;
                onSnoozePress?: () => void;
            }) => {
                return (
                    <div>
                        <button type="button" onClick={onPress}>
                            open-audition-{target.id}
                        </button>
                        <button type="button" onClick={onSnoozePress}>
                            snooze-audition-{target.id}
                        </button>
                        <button type="button" onClick={onArchivePress}>
                            archive-audition-{target.id}
                        </button>
                    </div>
                );
            },
        };
    },
);

vi.mock(
    "@/routes/home/components/OverviewScreenTabs/activities/components/JobActivityItem",
    () => {
        return {
            JobActivityItem: () => <div>job-row</div>,
        };
    },
);

vi.mock(
    "@/routes/home/components/OverviewScreenTabs/activities/components/InvoiceActivityItem",
    () => {
        return {
            InvoiceActivityItem: () => <div>invoice-row</div>,
        };
    },
);

vi.mock(
    "@/routes/home/components/OverviewScreenTabs/activities/components/UsageRightActivityItem",
    () => {
        return {
            UsageRightActivityItem: () => <div>usage-right-row</div>,
        };
    },
);

type ActivityRow = OverviewActivitiesSectionQuery["activities"]["data"][number];

function createAuditionActivity(id: string): ActivityRow {
    return {
        __typename: "Activity",
        id,
        trigger: ActivityTrigger.AuditionResponseDue,
        action: null,
        created_at: 1_736_929_200_000,
        targetable: {
            __typename: "Audition",
            id: `aud-${id}`,
            response_deadline: null,
            project_deadline: null,
            project_title: "Sample Project",
            status: AuditionStatus.Callback,
            sourceable: null,
        },
        notes: {
            __typename: "NotePaginator",
            paginatorInfo: {
                __typename: "PaginatorInfo",
                count: 0,
            },
        },
    };
}

function createQueryData(rows: ActivityRow[]): OverviewActivitiesSectionQuery {
    return {
        __typename: "Query",
        activities: {
            __typename: "ActivityPaginator",
            paginatorInfo: {
                __typename: "PaginatorInfo",
                total: rows.length,
            },
            data: rows,
        },
    };
}

beforeEach(() => {
    useMutationMock.mockReset();
    useSuspenseQueryMock.mockReset();
    toastSuccessMock.mockReset();
    toastErrorMock.mockReset();
    openAuditionMock.mockReset();
    openAgentMock.mockReset();
    openClientMock.mockReset();
    openExpenseMock.mockReset();
    openInvoiceMock.mockReset();
    openJobMock.mockReset();
    openNoteMock.mockReset();
    openPlatformMock.mockReset();
    openUsageRightMock.mockReset();
    openMenuDrawerMock.mockReset();
    openSearchMock.mockReset();
    openSettingsMock.mockReset();
    snoozeActivityMock.mockReset();
});

afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
});

describe("ActivitiesScreen", () => {
    it("renders an empty state when there are no activities", () => {
        useSuspenseQueryMock.mockReturnValue({
            data: createQueryData([]),
        });
        useMutationMock.mockReturnValue([vi.fn()]);

        render(<ActivitiesScreen />);

        expect(screen.getByText("No activities right now.")).toBeDefined();
    });

    it("archives an activity and triggers a success toast", async () => {
        const archiveMock = vi.fn(async () => {
            return {
                data: {
                    archiveActivity: {
                        __typename: "Activity",
                        id: "activity-1",
                        action: ActivityAction.Archived,
                    },
                },
            };
        });

        useSuspenseQueryMock.mockReturnValue({
            data: createQueryData([createAuditionActivity("activity-1")]),
        });
        useMutationMock.mockReturnValue([archiveMock]);

        render(<ActivitiesScreen />);

        fireEvent.click(screen.getByRole("button", { name: "archive-audition-aud-activity-1" }));

        await waitFor(() => {
            expect(archiveMock).toHaveBeenCalledTimes(1);
        });
        expect(toastSuccessMock).toHaveBeenCalledTimes(1);
        expect(toastErrorMock).not.toHaveBeenCalled();
    });

    it("shows an error toast when archive fails", async () => {
        const archiveMock = vi.fn(async () => {
            throw new Error("archive failed");
        });

        useSuspenseQueryMock.mockReturnValue({
            data: createQueryData([createAuditionActivity("activity-2")]),
        });
        useMutationMock.mockReturnValue([archiveMock]);

        render(<ActivitiesScreen />);

        fireEvent.click(screen.getByRole("button", { name: "archive-audition-aud-activity-2" }));

        await waitFor(() => {
            expect(archiveMock).toHaveBeenCalledTimes(1);
        });
        expect(toastErrorMock).toHaveBeenCalledTimes(1);
        expect(toastSuccessMock).not.toHaveBeenCalled();
    });

    it("routes audition presses and snooze actions through overview navigation handlers", () => {
        useSuspenseQueryMock.mockReturnValue({
            data: createQueryData([createAuditionActivity("activity-3")]),
        });
        useMutationMock.mockReturnValue([vi.fn()]);

        render(<ActivitiesScreen />);

        fireEvent.click(screen.getByRole("button", { name: "open-audition-aud-activity-3" }));
        fireEvent.click(screen.getByRole("button", { name: "snooze-audition-aud-activity-3" }));

        expect(openAuditionMock).toHaveBeenCalledWith("aud-activity-3");
        expect(snoozeActivityMock).toHaveBeenCalledWith({
            activityId: "activity-3",
            targetId: "aud-activity-3",
            targetType: "Audition",
        });
    });
});
