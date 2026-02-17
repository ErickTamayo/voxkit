import { create } from "zustand";

export type SessionStatus = "checking" | "authenticated" | "unauthenticated";

type SessionStore = {
    status: SessionStatus;
    setAuthenticated: () => void;
    setChecking: () => void;
    setUnauthenticated: () => void;
    setStatus: (status: SessionStatus) => void;
};

export const useSessionStore = create<SessionStore>((set) => ({
    status: "checking",
    setAuthenticated: () => {
        set((state) => {
            if (state.status === "authenticated") {
                return state;
            }

            return { status: "authenticated" };
        });
    },
    setChecking: () => {
        set((state) => {
            if (state.status === "checking") {
                return state;
            }

            return { status: "checking" };
        });
    },
    setUnauthenticated: () => {
        set((state) => {
            if (state.status === "unauthenticated") {
                return state;
            }

            return { status: "unauthenticated" };
        });
    },
    setStatus: (status) => {
        set((state) => {
            if (state.status === status) {
                return state;
            }

            return { status };
        });
    },
}));

export function setSessionAuthenticated(): void {
    useSessionStore.getState().setAuthenticated();
}

export function setSessionChecking(): void {
    useSessionStore.getState().setChecking();
}

export function setSessionUnauthenticated(): void {
    useSessionStore.getState().setUnauthenticated();
}
