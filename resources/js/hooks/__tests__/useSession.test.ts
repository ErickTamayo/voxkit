import { act, renderHook, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { LogoutDocument } from "@/routes/authentication/authentication.graphql.ts";
import { useSession } from "@/hooks/useSession";
import { useSessionStore } from "@/stores/sessionStore";

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

describe("useSession", () => {
    beforeEach(() => {
        clearAuthTokenMock.mockReset();
        shouldUseTokenAuthMock.mockReset();
        useMutationMock.mockReset();
        useQueryMock.mockReset();
        writeAuthTokenMock.mockReset();
        useSessionStore.getState().setStatus("checking");
    });

    it("sets authenticated status when me query returns user data", async () => {
        // Arrange
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

        // Act
        const { result, unmount } = renderHook(() => useSession());

        // Assert
        await waitFor(() => {
            expect(result.current.status).toBe("authenticated");
        });
        unmount();
    });

    it("sets unauthenticated status on unauthenticated me error", async () => {
        // Arrange
        shouldUseTokenAuthMock.mockReturnValue(false);
        useQueryMock.mockReturnValue({
            data: undefined,
            error: new Error("Unauthenticated."),
            loading: false,
            refetch: vi.fn(),
        });
        useMutationMock.mockImplementation(() => [vi.fn(), { loading: false }]);

        // Act
        const { result, unmount } = renderHook(() => useSession());

        // Assert
        await waitFor(() => {
            expect(result.current.status).toBe("unauthenticated");
        });
        unmount();
    });

    it("logout always sets unauthenticated and clears token even if mutation fails", async () => {
        // Arrange
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

        // Act
        const { result, unmount } = renderHook(() => useSession());
        await act(async () => {
            await expect(result.current.logout()).resolves.toBeUndefined();
        });

        // Assert
        expect(clearAuthTokenMock).toHaveBeenCalledTimes(1);
        expect(useSessionStore.getState().status).toBe("unauthenticated");
        unmount();
    });

    it("refreshSessionStatus sets unauthenticated when me refetch resolves to null", async () => {
        // Arrange
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

        // Act
        const { result, unmount } = renderHook(() => useSession());
        await act(async () => {
            await result.current.refreshSessionStatus();
        });

        // Assert
        expect(useSessionStore.getState().status).toBe("unauthenticated");
        unmount();
    });

    it("does not override authenticated status when session check query is skipped", () => {
        // Arrange
        useSessionStore.getState().setStatus("authenticated");

        shouldUseTokenAuthMock.mockReturnValue(false);
        useQueryMock.mockReturnValue({
            data: undefined,
            error: undefined,
            loading: false,
            refetch: vi.fn(),
        });
        useMutationMock.mockImplementation(() => [vi.fn(), { loading: false }]);

        // Act
        const { unmount } = renderHook(() => useSession());

        // Assert
        expect(useSessionStore.getState().status).toBe("authenticated");
        unmount();
    });
});
