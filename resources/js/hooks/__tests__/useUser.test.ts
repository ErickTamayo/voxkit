import { renderHook } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { useUser } from "@/hooks/useUser";

const useSuspenseQueryMock = vi.fn();

vi.mock("@apollo/client/react", async () => {
    const actual = await vi.importActual<typeof import("@apollo/client/react")>("@apollo/client/react");

    return {
        ...actual,
        useSuspenseQuery: (...args: unknown[]) => useSuspenseQueryMock(...args),
    };
});

describe("useUser", () => {
    beforeEach(() => {
        useSuspenseQueryMock.mockReset();
    });

    it("returns a non-null user from suspense query data", () => {
        // Arrange
        useSuspenseQueryMock.mockReturnValue({
            data: {
                me: {
                    id: "user_1",
                    name: "Test User",
                    email: "test@example.com",
                    email_verified_at: null,
                },
            },
        });

        // Act
        const { result } = renderHook(() => useUser());

        // Assert
        expect(result.current.user).toEqual({
            id: "user_1",
            name: "Test User",
            email: "test@example.com",
            email_verified_at: null,
        });
        expect(useSuspenseQueryMock).toHaveBeenCalledWith(expect.anything());
    });

    it("throws when user data is unexpectedly missing", () => {
        // Arrange
        useSuspenseQueryMock.mockReturnValue({
            data: {
                me: null,
            },
        });

        // Act / Assert
        expect(() => renderHook(() => useUser())).toThrowError(
            "Expected authenticated user data.",
        );
    });
});
