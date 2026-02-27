import {
    act,
    cleanup,
    fireEvent,
    render,
    screen,
    waitFor,
} from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import {
    OverviewScreenTabs,
} from "@/routes/home/components/OverviewScreenTabs/OverviewScreenTabs";
import type {
    OverviewScreenTabDefinition,
    OverviewScreenTabModule,
} from "@/routes/home/components/OverviewScreenTabs/types";

interface Deferred<T> {
    promise: Promise<T>;
    reject: (reason?: unknown) => void;
    resolve: (value: T) => void;
}

function createDeferred<T>(): Deferred<T> {
    let resolve!: (value: T) => void;
    let reject!: (reason?: unknown) => void;

    const promise = new Promise<T>((promiseResolve, promiseReject) => {
        resolve = promiseResolve;
        reject = promiseReject;
    });

    return {
        promise,
        resolve,
        reject,
    };
}

const ReportsScreen = () => {
    return <p>Reports screen content</p>;
};

const InboxScreen = () => {
    return <p>Inbox screen content</p>;
};

afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
});

describe("OverviewScreenTabs", () => {
    beforeEach(() => {
        Object.defineProperty(window, "matchMedia", {
            configurable: true,
            writable: true,
            value: vi.fn().mockImplementation(() => ({
                matches: false,
                media: "(min-width: 48rem)",
                onchange: null,
                addEventListener: vi.fn(),
                removeEventListener: vi.fn(),
                addListener: vi.fn(),
                removeListener: vi.fn(),
                dispatchEvent: vi.fn(),
            })),
        });
    });

    it("loads only the active tab screen initially", async () => {
        // Arrange
        const reportsDeferred = createDeferred<OverviewScreenTabModule>();
        const inboxDeferred = createDeferred<OverviewScreenTabModule>();
        const loadReportsScreen = vi.fn(() => reportsDeferred.promise);
        const loadInboxScreen = vi.fn(() => inboxDeferred.promise);
        const tabs: OverviewScreenTabDefinition[] = [
            {
                value: "reports",
                label: "Reports",
                loadScreen: loadReportsScreen,
                loadingFallback: <p>Loading reports...</p>,
            },
            {
                value: "inbox",
                label: "Inbox",
                loadScreen: loadInboxScreen,
                loadingFallback: <p>Loading inbox...</p>,
            },
        ];

        // Act
        render(<OverviewScreenTabs tabs={tabs} initialValue="reports" />);

        // Assert
        expect(loadReportsScreen).toHaveBeenCalledTimes(1);
        expect(loadInboxScreen).toHaveBeenCalledTimes(0);
        expect(screen.getByText("Loading reports...")).toBeDefined();

        await act(async () => {
            reportsDeferred.resolve({ default: ReportsScreen });
        });

        await waitFor(() => {
            expect(screen.getByText("Reports screen content")).toBeDefined();
        });
    });

    it("loads a tab screen when selected and does not reload previously loaded tabs", async () => {
        // Arrange
        const loadReportsScreen = vi.fn(async (): Promise<OverviewScreenTabModule> => {
            return { default: ReportsScreen };
        });
        const loadInboxScreen = vi.fn(async (): Promise<OverviewScreenTabModule> => {
            return { default: InboxScreen };
        });
        const tabs: OverviewScreenTabDefinition[] = [
            {
                value: "reports",
                label: "Reports",
                loadScreen: loadReportsScreen,
                loadingFallback: <p>Loading reports...</p>,
            },
            {
                value: "inbox",
                label: "Inbox",
                loadScreen: loadInboxScreen,
                loadingFallback: <p>Loading inbox...</p>,
            },
        ];

        // Act
        render(<OverviewScreenTabs tabs={tabs} initialValue="reports" />);

        // Assert initial load
        await waitFor(() => {
            expect(screen.getByText("Reports screen content")).toBeDefined();
        });
        expect(loadReportsScreen).toHaveBeenCalledTimes(1);
        expect(loadInboxScreen).toHaveBeenCalledTimes(0);

        // Act: switch to inbox
        fireEvent.click(screen.getByRole("tab", { name: "Inbox" }));

        // Assert inbox lazy loads
        await waitFor(() => {
            expect(screen.getByText("Inbox screen content")).toBeDefined();
        });
        expect(loadInboxScreen).toHaveBeenCalledTimes(1);

        // Act: switch back to reports
        fireEvent.click(screen.getByRole("tab", { name: "Reports" }));

        // Assert reports screen was preserved (no second lazy import call)
        expect(loadReportsScreen).toHaveBeenCalledTimes(1);
    });

    it("notifies tab presses even when the active tab is pressed again", async () => {
        // Arrange
        const onTabPress = vi.fn();
        const tabs: OverviewScreenTabDefinition[] = [
            {
                value: "reports",
                label: "Reports",
                loadScreen: async (): Promise<OverviewScreenTabModule> => {
                    return { default: ReportsScreen };
                },
                loadingFallback: <p>Loading reports...</p>,
            },
            {
                value: "inbox",
                label: "Inbox",
                loadScreen: async (): Promise<OverviewScreenTabModule> => {
                    return { default: InboxScreen };
                },
                loadingFallback: <p>Loading inbox...</p>,
            },
        ];

        // Act
        render(
            <OverviewScreenTabs
                tabs={tabs}
                initialValue="reports"
                onTabPress={onTabPress}
            />,
        );

        await waitFor(() => {
            expect(screen.getByText("Reports screen content")).toBeDefined();
        });

        fireEvent.click(screen.getByRole("tab", { name: "Reports" }));
        fireEvent.click(screen.getByRole("tab", { name: "Inbox" }));

        // Assert
        expect(onTabPress).toHaveBeenCalledTimes(2);
        expect(onTabPress).toHaveBeenNthCalledWith(1, "reports");
        expect(onTabPress).toHaveBeenNthCalledWith(2, "inbox");
    });

    it("retries a failed tab lazy load when the fallback retry action is pressed", async () => {
        // Arrange
        const consoleErrorSpy = vi.spyOn(console, "error").mockImplementation(() => {});
        let reportsAttempt = 0;
        const loadReportsScreen = vi.fn(async (): Promise<OverviewScreenTabModule> => {
            reportsAttempt += 1;

            if (reportsAttempt === 1) {
                throw new Error("Lazy import failed.");
            }

            return { default: ReportsScreen };
        });
        const tabs: OverviewScreenTabDefinition[] = [
            {
                value: "reports",
                label: "Reports",
                loadScreen: loadReportsScreen,
                loadingFallback: <p>Loading reports...</p>,
            },
            {
                value: "inbox",
                label: "Inbox",
                loadScreen: async (): Promise<OverviewScreenTabModule> => {
                    return { default: InboxScreen };
                },
                loadingFallback: <p>Loading inbox...</p>,
            },
        ];

        // Act
        render(<OverviewScreenTabs tabs={tabs} initialValue="reports" />);

        // Assert failure UI
        await waitFor(() => {
            expect(screen.getByText("Could not load Reports.")).toBeDefined();
        });
        expect(loadReportsScreen).toHaveBeenCalledTimes(1);

        // Act: retry
        fireEvent.click(screen.getByRole("button", { name: "Try again" }));

        // Assert retry imports and content renders
        await waitFor(() => {
            expect(loadReportsScreen).toHaveBeenCalledTimes(2);
        });
        await waitFor(() => {
            expect(screen.getByText("Reports screen content")).toBeDefined();
        });

        consoleErrorSpy.mockRestore();
    });
});
