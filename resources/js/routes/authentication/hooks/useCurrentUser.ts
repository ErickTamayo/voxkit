import { useQuery } from "@apollo/client/react";
import type { MeQuery } from "@/graphql/root.graphql.ts";
import { MeDocument } from "@/graphql/root.graphql.ts";

export function useCurrentUser(): {
    isCheckingSession: boolean;
    refetchSession: () => Promise<unknown>;
    user: MeQuery["me"] | null;
} {
    const { data, loading, refetch } = useQuery(MeDocument, {
        fetchPolicy: "network-only",
        errorPolicy: "all",
    });

    return {
        user: data?.me ?? null,
        isCheckingSession: loading,
        refetchSession: async () => refetch(),
    };
}
