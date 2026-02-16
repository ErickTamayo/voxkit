import { skipToken, useSuspenseQuery } from "@apollo/client/react";
import type { MeQuery } from "@/graphql/root.graphql.ts";
import { MeDocument } from "@/graphql/root.graphql.ts";
import { readAuthToken, shouldUseTokenAuth } from "@/lib/authSession";

export function useCurrentUser(): {
    refetchSession: () => Promise<unknown>;
    user: MeQuery["me"] | null;
} {
    const useTokenAuth = shouldUseTokenAuth();
    const shouldSkipQuery = useTokenAuth && readAuthToken() === null;
    const { data, refetch } = useSuspenseQuery(
        MeDocument,
        shouldSkipQuery
            ? skipToken
            : {
                  fetchPolicy: "network-only",
                  errorPolicy: "all",
              },
    );

    return {
        user: data?.me ?? null,
        refetchSession: async () => {
            if (useTokenAuth && readAuthToken() === null) {
                return null;
            }

            return refetch();
        },
    };
}
