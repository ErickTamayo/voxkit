import { useEffect, type ComponentType, type ReactNode } from "react";
import { Redirect, Route, type RouteProps } from "wouter";
import { useSession } from "@/hooks/useSession";
import {
    registerPrivateRoutePattern,
    unregisterPrivateRoutePattern,
} from "@/lib/privateRoutes";

type LayoutComponent = ComponentType<{ children: ReactNode }>;
type PageComponent = ComponentType;

type AuthenticatedRouteProps = Omit<RouteProps, "component" | "children"> & {
    component: PageComponent;
    fallback?: ReactNode;
    layouts?: LayoutComponent[];
    path: string | string[];
};

export function AuthenticatedRoute({
    fallback = null,
    layouts = [],
    component,
    ...props
}: AuthenticatedRouteProps): React.JSX.Element {
    const { status } = useSession();
    const Page = component;

    useEffect(() => {
        const privateRoutePatterns = Array.isArray(props.path) ? props.path : [props.path];

        for (const pattern of privateRoutePatterns) {
            registerPrivateRoutePattern(pattern);
        }

        return () => {
            for (const pattern of privateRoutePatterns) {
                unregisterPrivateRoutePattern(pattern);
            }
        };
    }, [props.path]);

    const renderedPage = layouts.reduceRight<ReactNode>(
        (content, Layout) => <Layout>{content}</Layout>,
        <Page />,
    );

    return (
        <Route {...props}>
            {status === "authenticated"
                ? renderedPage
                : status === "checking"
                    ? fallback
                    : <Redirect to="/signin" />}
        </Route>
    );
}
