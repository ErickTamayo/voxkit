import { act, createElement } from "react";
import { createRoot } from "react-dom/client";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { useUser } from "@/hooks/useUser";

(globalThis as { IS_REACT_ACT_ENVIRONMENT?: boolean }).IS_REACT_ACT_ENVIRONMENT = true;

const useSuspenseQueryMock = vi.fn();

vi.mock("@apollo/client/react", async () => {
    const actual = await vi.importActual<typeof import("@apollo/client/react")>("@apollo/client/react");

    return {
        ...actual,
        useSuspenseQuery: (...args: unknown[]) => useSuspenseQueryMock(...args),
    };
});

function renderUseUser(): {
    current: ReturnType<typeof useUser>;
    cleanup: () => void;
} {
    const container = document.createElement("div");
    const root = createRoot(container);
    let hookValue: ReturnType<typeof useUser> | null = null;

    function Probe(): null {
        hookValue = useUser();

        return null;
    }

    act(() => {
        root.render(createElement(Probe));
    });

    if (hookValue === null) {
        throw new Error("Hook probe did not capture useUser value.");
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

describe("useUser", () => {
    beforeEach(() => {
        useSuspenseQueryMock.mockReset();
    });

    it("returns a non-null user from suspense query data", () => {
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

        const hook = renderUseUser();

        expect(hook.current.user).toEqual({
            id: "user_1",
            name: "Test User",
            email: "test@example.com",
            email_verified_at: null,
        });
        expect(useSuspenseQueryMock).toHaveBeenCalledWith(expect.anything(), {
            fetchPolicy: "network-only",
        });
        hook.cleanup();
    });

    it("throws when user data is unexpectedly missing", () => {
        useSuspenseQueryMock.mockReturnValue({
            data: {
                me: null,
            },
        });

        expect(() => renderUseUser()).toThrowError("Expected authenticated user data.");
    });
});
