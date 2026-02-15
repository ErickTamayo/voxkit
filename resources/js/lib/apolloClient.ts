import { ApolloClient, InMemoryCache, createHttpLink } from "@apollo/client";
import { setContext } from "@apollo/client/link/context";

const GRAPHQL_ENDPOINT = import.meta.env.VITE_GRAPHQL_ENDPOINT ?? "/graphql";

function getCsrfToken(): string | null {
    const csrfMetaTag = document.querySelector('meta[name="csrf-token"]');

    return csrfMetaTag?.getAttribute("content") ?? null;
}

const httpLink = createHttpLink({
    uri: GRAPHQL_ENDPOINT,
    credentials: "include",
});

const csrfLink = setContext((_, context) => {
    const csrfToken = getCsrfToken();

    return {
        headers: {
            ...context.headers,
            "X-Requested-With": "XMLHttpRequest",
            ...(csrfToken !== null ? { "X-CSRF-TOKEN": csrfToken } : {}),
        },
    };
});

export const apolloClient = new ApolloClient({
    link: csrfLink.concat(httpLink),
    cache: new InMemoryCache(),
    defaultOptions: {
        watchQuery: {
            errorPolicy: "all",
        },
        query: {
            errorPolicy: "all",
        },
        mutate: {
            errorPolicy: "all",
        },
    },
});
