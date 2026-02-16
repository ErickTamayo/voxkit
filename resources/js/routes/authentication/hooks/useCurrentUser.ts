import { useQuery } from "@apollo/client/react";
import { useEffect, useState } from "react";
import type { MeQuery } from "@/graphql/root.graphql.ts";
import { MeDocument } from "@/graphql/root.graphql.ts";
import { readAuthToken, shouldUseTokenAuth } from "@/lib/authSession";
import { ensureSessionCsrfCookie } from "@/lib/csrf";

export function useCurrentUser(): {
    isCheckingSession: boolean;
    refetchSession: () => Promise<unknown>;
    user: MeQuery["me"] | null;
} {
    const useTokenAuth = shouldUseTokenAuth();
    const [isWebCsrfReady, setIsWebCsrfReady] = useState<boolean>(useTokenAuth);

    useEffect(() => {
        if (useTokenAuth) {
            setIsWebCsrfReady(true);

            return;
        }

        let isCancelled = false;

        void ensureSessionCsrfCookie()
            .catch(() => null)
            .finally(() => {
                if (!isCancelled) {
                    setIsWebCsrfReady(true);
                }
            });

        return () => {
            isCancelled = true;
        };
    }, [useTokenAuth]);

    const shouldSkipQuery = (useTokenAuth && readAuthToken() === null) || !isWebCsrfReady;
    const { data, loading, refetch } = useQuery(MeDocument, {
        fetchPolicy: "network-only",
        errorPolicy: "all",
        skip: shouldSkipQuery,
    });
    const isBootstrappingWebCsrf = !useTokenAuth && !isWebCsrfReady;

    return {
        user: data?.me ?? null,
        isCheckingSession: isBootstrappingWebCsrf || (!shouldSkipQuery && loading),
        refetchSession: async () => {
            if ((useTokenAuth && readAuthToken() === null) || !isWebCsrfReady) {
                return null;
            }

            return refetch();
        },
    };
}
