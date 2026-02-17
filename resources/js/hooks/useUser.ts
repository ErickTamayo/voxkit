import { useSuspenseQuery } from "@apollo/client/react";
import type { MeQuery } from "@/graphql/root.graphql.ts";
import { MeDocument } from "@/graphql/root.graphql.ts";

export function useUser(): {
    user: MeQuery["me"];
} {
    const { data } = useSuspenseQuery(MeDocument, {
        fetchPolicy: "network-only",
    });

    if (data.me === null || data.me === undefined) {
        throw new Error("Expected authenticated user data.");
    }

    return {
        user: data.me,
    };
}
