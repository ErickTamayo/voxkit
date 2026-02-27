import type { FC, ReactNode } from "react";
import type { Meta, StoryObj } from "@storybook/react-vite";
import { ActivityTrigger, AuditionStatus } from "@/graphql/types";
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
import type {
    AuditionActivityTarget,
    InvoiceActivityTarget,
    JobActivityTarget,
    OverviewActivityEntry,
    UsageRightActivityTarget,
} from "@/routes/home/components/OverviewScreenTabs/activities/types";

const DAY_MS = 86_400_000;
const now = Date.now();

const auditionAction: Pick<OverviewActivityEntry, "created_at" | "trigger"> = {
    created_at: now - DAY_MS,
    trigger: ActivityTrigger.AuditionResponseDue,
};

const auditionTarget: AuditionActivityTarget = {
    __typename: "Audition",
    id: "audition-1",
    project_title: "Nike Campaign VO",
    project_deadline: null,
    response_deadline: now - DAY_MS * 2,
    sourceable: {
        __typename: "Platform",
        id: "platform-1",
        name: "Voices.com",
    },
    status: AuditionStatus.Callback,
};

const jobAction: Pick<OverviewActivityEntry, "created_at" | "trigger"> = {
    created_at: now - DAY_MS * 2,
    trigger: ActivityTrigger.JobDeliveryDue,
};

const jobTarget: JobActivityTarget = {
    __typename: "Job",
    id: "job-1",
    project_title: "Trailer Narration",
    session_date: now + DAY_MS,
    delivery_deadline: now - DAY_MS * 3,
};

const invoiceAction: Pick<OverviewActivityEntry, "created_at" | "trigger"> = {
    created_at: now - DAY_MS * 4,
    trigger: ActivityTrigger.InvoiceOverdue,
};

const invoiceTarget: InvoiceActivityTarget = {
    __typename: "Invoice",
    id: "invoice-1",
    job: {
        __typename: "Job",
        project_title: "Corporate Explainer",
    },
    total: {
        __typename: "MonetaryAmount",
        original: {
            __typename: "Money",
            currency: "USD",
            amount_cents: 287_000,
        },
    },
};

const usageRightAction: Pick<OverviewActivityEntry, "created_at" | "trigger"> = {
    created_at: now - DAY_MS * 5,
    trigger: ActivityTrigger.UsageRightsExpiring,
};

const usageRightTarget: UsageRightActivityTarget = {
    __typename: "UsageRight",
    id: "usage-right-1",
    expiration_date: now + DAY_MS * 7,
    usable: {
        __typename: "Audition",
        id: "audition-2",
        project_title: "Tech Product Launch",
    },
};

interface ActivitiesItemsStoryFrameProps {
    children: ReactNode;
}

const ActivitiesItemsStoryFrame: FC<ActivitiesItemsStoryFrameProps> = ({
    children,
}) => {
    return (
        <main className="bg-background min-h-full p-4">
            <div className="mx-auto flex min-h-[calc(100dvh-2rem)] w-full max-w-4xl flex-col gap-4">
                <header className="space-y-2 px-1 pt-2">
                    <p className="text-sm font-medium tracking-wide text-muted-foreground uppercase">
                        Home / Capacitor
                    </p>
                    <h1 className="text-2xl font-semibold">Overview Activities Items</h1>
                    <p className="text-sm text-muted-foreground">
                        Leaf item parity pass for Activities rows.
                    </p>
                </header>

                <section className="-mx-4 overflow-hidden border-y border-border/80 bg-background">
                    {children}
                </section>
            </div>
        </main>
    );
};

const AllItemsStory: FC = () => {
    return (
        <ActivitiesItemsStoryFrame>
            <AuditionActivityItem
                action={auditionAction}
                target={auditionTarget}
                onArchivePress={() => {}}
            />
            <JobActivityItem
                action={jobAction}
                target={jobTarget}
                onArchivePress={() => {}}
            />
            <InvoiceActivityItem
                action={invoiceAction}
                target={invoiceTarget}
                onArchivePress={() => {}}
            />
            <UsageRightActivityItem
                action={usageRightAction}
                target={usageRightTarget}
                onArchivePress={() => {}}
            />
        </ActivitiesItemsStoryFrame>
    );
};

const AuditionOnlyStory: FC = () => {
    return (
        <ActivitiesItemsStoryFrame>
            <AuditionActivityItem
                action={auditionAction}
                target={auditionTarget}
                onArchivePress={() => {}}
            />
        </ActivitiesItemsStoryFrame>
    );
};

const JobOnlyStory: FC = () => {
    return (
        <ActivitiesItemsStoryFrame>
            <JobActivityItem
                action={jobAction}
                target={jobTarget}
                onArchivePress={() => {}}
            />
        </ActivitiesItemsStoryFrame>
    );
};

const InvoiceOnlyStory: FC = () => {
    return (
        <ActivitiesItemsStoryFrame>
            <InvoiceActivityItem
                action={invoiceAction}
                target={invoiceTarget}
                onArchivePress={() => {}}
            />
        </ActivitiesItemsStoryFrame>
    );
};

const UsageRightOnlyStory: FC = () => {
    return (
        <ActivitiesItemsStoryFrame>
            <UsageRightActivityItem
                action={usageRightAction}
                target={usageRightTarget}
                onArchivePress={() => {}}
            />
        </ActivitiesItemsStoryFrame>
    );
};

const meta = {
    title: "Screens/Home/Capacitor/Overview Activities Items",
    component: AllItemsStory,
    parameters: {
        layout: "fullscreen",
    },
} satisfies Meta<typeof AllItemsStory>;

export default meta;

type Story = StoryObj<typeof meta>;

export const AllItems: Story = {
    globals: {
        platformTarget: "capacitor",
        safeAreaPreset: "iphone",
    },
    parameters: {
        viewport: {
            defaultViewport: "mobileSheet",
        },
    },
};

export const Audition: Story = {
    render: () => <AuditionOnlyStory />,
    globals: AllItems.globals,
    parameters: AllItems.parameters,
};

export const Job: Story = {
    render: () => <JobOnlyStory />,
    globals: AllItems.globals,
    parameters: AllItems.parameters,
};

export const Invoice: Story = {
    render: () => <InvoiceOnlyStory />,
    globals: AllItems.globals,
    parameters: AllItems.parameters,
};

export const UsageRight: Story = {
    render: () => <UsageRightOnlyStory />,
    globals: AllItems.globals,
    parameters: AllItems.parameters,
};
