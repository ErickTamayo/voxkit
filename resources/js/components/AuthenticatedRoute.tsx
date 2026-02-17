import type { ComponentType, ReactNode } from "react";
import { Redirect, Route, type RouteProps } from "wouter";
import { useSession } from "@/hooks/useSession";

type LayoutComponent = ComponentType<{ children: ReactNode }>;
type PageComponent = ComponentType;

type AuthenticatedRouteProps = Omit<RouteProps, "component" | "children"> & {
    component: PageComponent;
    layouts?: LayoutComponent[];
};

export function AuthenticatedRoute({
    layouts = [],
    component,
    ...props
}: AuthenticatedRouteProps): React.JSX.Element {
    const { status } = useSession();
    const Page = component;

    const renderedPage = layouts.reduceRight<ReactNode>(
        (content, Layout) => <Layout>{content}</Layout>,
        <Page />,
    );

    return (
        <Route {...props}>
            {status === "authenticated"
                ? renderedPage
                : status === "checking"
                    ? (
                        <p className="px-4 py-6 text-sm text-muted-foreground">Checking your session...</p>
                    )
                    : <Redirect to="/signin" />}
        </Route>
    );
}
