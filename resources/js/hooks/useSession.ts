import { useMutation, useQuery } from "@apollo/client/react";
import { useEffect } from "react";
import { AuthMode } from "@/graphql/types";
import { MeDocument } from "@/graphql/root.graphql.ts";
import {
    clearAuthToken,
    shouldUseTokenAuth,
    writeAuthToken,
} from "@/lib/authSession";
import { isUnauthenticatedError } from "@/lib/authErrors";
import {
    AuthenticateWithCodeDocument,
    RequestAuthenticationCodeDocument,
    LogoutDocument,
} from "@/routes/authentication/authentication.graphql.ts";
import { type SessionStatus, useSessionStore } from "@/stores/sessionStore";

type AuthenticationActionResult = {
    errorMessage: string | null;
    ok: boolean;
};

export function useSession(): {
    authenticateWithCode: (input: {
        code: string;
        email: string;
    }) => Promise<AuthenticationActionResult>;
    isAuthenticatingWithCode: boolean;
    isLoggingOut: boolean;
    isRequestingAuthenticationCode: boolean;
    refreshSessionStatus: () => Promise<void>;
    requestAuthenticationCode: (email: string) => Promise<AuthenticationActionResult>;
    status: SessionStatus;
    logout: () => Promise<void>;
} {
    const status = useSessionStore((state) => state.status);
    const setAuthenticated = useSessionStore((state) => state.setAuthenticated);
    const setChecking = useSessionStore((state) => state.setChecking);
    const setUnauthenticated = useSessionStore((state) => state.setUnauthenticated);
    const useTokenAuth = shouldUseTokenAuth();
    const shouldCheckSession = status === "checking";

    const { data, error, loading, refetch } = useQuery(MeDocument, {
        errorPolicy: "all",
        fetchPolicy: "network-only",
        notifyOnNetworkStatusChange: true,
        skip: !shouldCheckSession,
    });

    const [requestAuthenticationCodeMutation, { loading: isRequestingAuthenticationCode }] = useMutation(RequestAuthenticationCodeDocument);
    const [authenticateWithCodeMutation, { loading: isAuthenticatingWithCode }] = useMutation(AuthenticateWithCodeDocument);
    const [logoutMutation, { loading: isLoggingOut }] = useMutation(LogoutDocument);

    useEffect(() => {
        if (!shouldCheckSession) {
            return;
        }

        if (loading) {
            return;
        }

        if (data?.me !== undefined && data.me !== null) {
            setAuthenticated();

            return;
        }

        if (isUnauthenticatedError(error) || data?.me === null || data?.me === undefined) {
            setUnauthenticated();
        }
    }, [data?.me, error, loading, setAuthenticated, setUnauthenticated, shouldCheckSession]);

    async function refreshSessionStatus(): Promise<void> {
        setChecking();

        try {
            const result = await refetch();
            if (result.data?.me !== undefined && result.data.me !== null) {
                setAuthenticated();

                return;
            }
        } catch (refetchError) {
            if (!isUnauthenticatedError(refetchError)) {
                setUnauthenticated();

                return;
            }
        }

        setUnauthenticated();
    }

    async function requestAuthenticationCode(email: string): Promise<AuthenticationActionResult> {
        try {
            const result = await requestAuthenticationCodeMutation({
                variables: {
                    input: {
                        email,
                    },
                },
            });

            const response = result.data?.requestAuthenticationCode;
            switch (response?.__typename) {
                case "RequestAuthenticationCodeSuccess":
                    return {
                        ok: true,
                        errorMessage: null,
                    };
                case "AuthenticationRateLimitError":
                    return {
                        ok: false,
                        errorMessage: response.message,
                    };
                default:
                    return {
                        ok: false,
                        errorMessage: "Failed to send code.",
                    };
            }
        } catch (requestError) {
            return {
                ok: false,
                errorMessage: requestError instanceof Error ? requestError.message : "Failed to send code.",
            };
        }
    }

    async function authenticateWithCode(input: {
        code: string;
        email: string;
    }): Promise<AuthenticationActionResult> {
        try {
            const result = await authenticateWithCodeMutation({
                variables: {
                    input: {
                        email: input.email,
                        code: input.code,
                        mode: useTokenAuth ? AuthMode.Token : AuthMode.Session,
                        ...(useTokenAuth ? { device_name: "capacitor_app" } : {}),
                    },
                },
            });

            const response = result.data?.authenticateWithCode;
            switch (response?.__typename) {
                case "AuthenticateWithCodeTokenSuccess":
                    await writeAuthToken(response.token);
                    setAuthenticated();

                    return {
                        ok: true,
                        errorMessage: null,
                    };
                case "AuthenticateWithCodeSessionSuccess":
                    await clearAuthToken();
                    setAuthenticated();

                    return {
                        ok: true,
                        errorMessage: null,
                    };
                case "AuthenticateWithCodeInvalidCodeError":
                case "AuthenticationRateLimitError":
                    setUnauthenticated();

                    return {
                        ok: false,
                        errorMessage: response.message,
                    };
                default:
                    setUnauthenticated();

                    return {
                        ok: false,
                        errorMessage: "Invalid or expired code.",
                    };
            }
        } catch (authenticationError) {
            setUnauthenticated();

            return {
                ok: false,
                errorMessage: authenticationError instanceof Error ? authenticationError.message : "Invalid or expired code.",
            };
        }
    }

    async function logout(): Promise<void> {
        setUnauthenticated();
        await clearAuthToken();

        try {
            await logoutMutation();
        } catch {
            // Proceed with local logout regardless of remote mutation outcome.
        }
    }

    return {
        status,
        requestAuthenticationCode,
        isRequestingAuthenticationCode,
        authenticateWithCode,
        isAuthenticatingWithCode,
        logout,
        isLoggingOut,
        refreshSessionStatus,
    };
}
