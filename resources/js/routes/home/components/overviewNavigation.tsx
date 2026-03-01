import {
    createContext,
    useContext,
    type FC,
    type PropsWithChildren,
} from "react";

const OVERVIEW_NAVIGATION_ROUTE_CONTRACTS = {
    activities: {
        audition: "/auditions/:auditionId",
        invoice: "/invoices/:invoiceId",
        job: "/jobs/:jobId",
        usageRight: "/usage-rights/:usageRightId",
    },
    searchDetails: {
        agent: "/agents/:agentId",
        client: "/clients/:clientId",
        expense: "/expenses/:expenseId",
        note: "/notes/:noteId",
        platform: "/platforms/:platformId",
    },
    header: {
        menuDrawer: "drawer:right",
        search: "/search",
        settings: "/settings",
    },
} as const;

interface OverviewSnoozePayload {
    activityId: string;
    targetId: string;
    targetType: "Audition" | "Invoice" | "Job" | "UsageRight";
}

interface OverviewNavigationHandlers {
    onOpenAgent: (agentId: string) => void;
    onOpenAudition: (auditionId: string) => void;
    onOpenClient: (clientId: string) => void;
    onOpenExpense: (expenseId: string) => void;
    onOpenInvoice: (invoiceId: string) => void;
    onOpenJob: (jobId: string) => void;
    onOpenMenuDrawer: () => void;
    onOpenNote: (noteId: string) => void;
    onOpenPlatform: (platformId: string) => void;
    onOpenSearch: () => void;
    onOpenSettings: () => void;
    onOpenUsageRight: (usageRightId: string) => void;
    onSnoozeActivity: (payload: OverviewSnoozePayload) => void;
}

function createOverviewConsoleNavigationHandlers(): OverviewNavigationHandlers {
    return {
        onOpenAgent: (agentId) => {
            console.info("[overview-nav] open agent", {
                agentId,
                contract: OVERVIEW_NAVIGATION_ROUTE_CONTRACTS.searchDetails.agent,
            });
        },
        onOpenAudition: (auditionId) => {
            console.info("[overview-nav] open audition", {
                auditionId,
                contract: OVERVIEW_NAVIGATION_ROUTE_CONTRACTS.activities.audition,
            });
        },
        onOpenClient: (clientId) => {
            console.info("[overview-nav] open client", {
                clientId,
                contract: OVERVIEW_NAVIGATION_ROUTE_CONTRACTS.searchDetails.client,
            });
        },
        onOpenExpense: (expenseId) => {
            console.info("[overview-nav] open expense", {
                expenseId,
                contract: OVERVIEW_NAVIGATION_ROUTE_CONTRACTS.searchDetails.expense,
            });
        },
        onOpenJob: (jobId) => {
            console.info("[overview-nav] open job", {
                jobId,
                contract: OVERVIEW_NAVIGATION_ROUTE_CONTRACTS.activities.job,
            });
        },
        onOpenInvoice: (invoiceId) => {
            console.info("[overview-nav] open invoice", {
                invoiceId,
                contract: OVERVIEW_NAVIGATION_ROUTE_CONTRACTS.activities.invoice,
            });
        },
        onOpenUsageRight: (usageRightId) => {
            console.info("[overview-nav] open usage right", {
                usageRightId,
                contract: OVERVIEW_NAVIGATION_ROUTE_CONTRACTS.activities.usageRight,
            });
        },
        onOpenSettings: () => {
            console.info("[overview-nav] open settings", {
                contract: OVERVIEW_NAVIGATION_ROUTE_CONTRACTS.header.settings,
            });
        },
        onOpenSearch: () => {
            console.info("[overview-nav] open search", {
                contract: OVERVIEW_NAVIGATION_ROUTE_CONTRACTS.header.search,
            });
        },
        onOpenMenuDrawer: () => {
            console.info("[overview-nav] open menu drawer", {
                contract: OVERVIEW_NAVIGATION_ROUTE_CONTRACTS.header.menuDrawer,
            });
        },
        onOpenNote: (noteId) => {
            console.info("[overview-nav] open note", {
                noteId,
                contract: OVERVIEW_NAVIGATION_ROUTE_CONTRACTS.searchDetails.note,
            });
        },
        onOpenPlatform: (platformId) => {
            console.info("[overview-nav] open platform", {
                platformId,
                contract: OVERVIEW_NAVIGATION_ROUTE_CONTRACTS.searchDetails.platform,
            });
        },
        onSnoozeActivity: (payload) => {
            console.info("[overview-nav] snooze activity", payload);
        },
    };
}

const OverviewNavigationContext = createContext<OverviewNavigationHandlers>(
    createOverviewConsoleNavigationHandlers(),
);

interface OverviewNavigationProviderProps extends PropsWithChildren {
    handlers: OverviewNavigationHandlers;
}

const OverviewNavigationProvider: FC<OverviewNavigationProviderProps> = ({
    handlers,
    children,
}) => {
    return (
        <OverviewNavigationContext.Provider value={handlers}>
            {children}
        </OverviewNavigationContext.Provider>
    );
};

function useOverviewNavigation(): OverviewNavigationHandlers {
    return useContext(OverviewNavigationContext);
}

export {
    OVERVIEW_NAVIGATION_ROUTE_CONTRACTS,
    createOverviewConsoleNavigationHandlers,
    OverviewNavigationProvider,
    useOverviewNavigation,
};
export type { OverviewNavigationHandlers, OverviewSnoozePayload };
