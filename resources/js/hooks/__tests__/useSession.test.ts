import { act, createElement } from "react";
import { createRoot } from "react-dom/client";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { LogoutDocument } from "@/routes/authentication/authentication.graphql.ts";
import { useSession } from "@/hooks/useSession";
import { useSessionStore } from "@/stores/sessionStore";

(globalThis as { IS_REACT_ACT_ENVIRONMENT?: boolean }).IS_REACT_ACT_ENVIRONMENT = true;

const clearAuthTokenMock = vi.fn();
const shouldUseTokenAuthMock = vi.fn();
const useMutationMock = vi.fn();
const useQueryMock = vi.fn();
const writeAuthTokenMock = vi.fn();

vi.mock("@apollo/client/react", async () => {
    const actual = await vi.importActual<typeof import("@apollo/client/react")>("@apollo/client/react");

    return {
        ...actual,
        useMutation: (...args: unknown[]) => useMutationMock(...args),
        useQuery: (...args: unknown[]) => useQueryMock(...args),
    };
});

vi.mock("@/lib/authSession", () => ({
    clearAuthToken: () => clearAuthTokenMock(),
    shouldUseTokenAuth: () => shouldUseTokenAuthMock(),
    writeAuthToken: (token: string) => writeAuthTokenMock(token),
}));

function renderUseSession(): {
    current: ReturnType<typeof useSession>;
    cleanup: () => void;
} {
    const container = document.createElement("div");
    const root = createRoot(container);
    let hookValue: ReturnType<typeof useSession> | null = null;

    function Probe(): null {
        hookValue = useSession();

        return null;
    }

    act(() => {
        root.render(createElement(Probe));
    });

    if (hookValue === null) {
        throw new Error("Hook probe did not capture useSession value.");
    }

    return {
        current: hookValue,
        cleanup: () => {
            act(() => {
                root.unmount();
            });
            container.remove();
        },
    };
}

describe("useSession", () => {
    beforeEach(() => {
        clearAuthTokenMock.mockReset();
        shouldUseTokenAuthMock.mockReset();
        useMutationMock.mockReset();
        useQueryMock.mockReset();
        writeAuthTokenMock.mockReset();
        useSessionStore.getState().setStatus("checking");
    });

    it("sets authenticated status when me query returns user data", () => {
        shouldUseTokenAuthMock.mockReturnValue(false);
        useQueryMock.mockReturnValue({
            data: {
                me: {
                    id: "user_1",
                },
            },
            error: undefined,
            loading: false,
            refetch: vi.fn(),
        });
        useMutationMock.mockImplementation(() => [vi.fn(), { loading: false }]);

        const hook = renderUseSession();

        expect(hook.current.status).toBe("authenticated");
        hook.cleanup();
    });

    it("sets unauthenticated status on unauthenticated me error", () => {
        shouldUseTokenAuthMock.mockReturnValue(false);
        useQueryMock.mockReturnValue({
            data: undefined,
            error: new Error("Unauthenticated."),
            loading: false,
            refetch: vi.fn(),
        });
        useMutationMock.mockImplementation(() => [vi.fn(), { loading: false }]);

        const hook = renderUseSession();

        expect(hook.current.status).toBe("unauthenticated");
        hook.cleanup();
    });

    it("logout always sets unauthenticated and clears token even if mutation fails", async () => {
        const logoutMutationMock = vi.fn().mockRejectedValue(new Error("Network error"));
        const refetchMock = vi.fn().mockResolvedValue({
            data: {
                me: {
                    id: "user_1",
                },
            },
        });

        shouldUseTokenAuthMock.mockReturnValue(true);
        useQueryMock.mockReturnValue({
            data: {
                me: {
                    id: "user_1",
                },
            },
            error: undefined,
            loading: false,
            refetch: refetchMock,
        });
        useMutationMock.mockImplementation((document: unknown) => {
            if (document === LogoutDocument) {
                return [logoutMutationMock, { loading: false }];
            }

            return [vi.fn(), { loading: false }];
        });

        const hook = renderUseSession();
        await act(async () => {
            await expect(hook.current.logout()).resolves.toBeUndefined();
        });

        expect(clearAuthTokenMock).toHaveBeenCalledTimes(1);
        expect(useSessionStore.getState().status).toBe("unauthenticated");
        hook.cleanup();
    });

    it("refreshSessionStatus sets unauthenticated when me refetch resolves to null", async () => {
        const refetchMock = vi.fn().mockResolvedValue({
            data: {
                me: null,
            },
        });

        shouldUseTokenAuthMock.mockReturnValue(false);
        useQueryMock.mockReturnValue({
            data: {
                me: {
                    id: "user_1",
                },
            },
            error: undefined,
            loading: false,
            refetch: refetchMock,
        });
        useMutationMock.mockImplementation(() => [vi.fn(), { loading: false }]);

        const hook = renderUseSession();
        await act(async () => {
            await hook.current.refreshSessionStatus();
        });

        expect(useSessionStore.getState().status).toBe("unauthenticated");
        hook.cleanup();
    });

    it("does not override authenticated status when session check query is skipped", () => {
        useSessionStore.getState().setStatus("authenticated");

        shouldUseTokenAuthMock.mockReturnValue(false);
        useQueryMock.mockReturnValue({
            data: undefined,
            error: undefined,
            loading: false,
            refetch: vi.fn(),
        });
        useMutationMock.mockImplementation(() => [vi.fn(), { loading: false }]);

        const hook = renderUseSession();

        expect(useSessionStore.getState().status).toBe("authenticated");
        hook.cleanup();
    });
});
