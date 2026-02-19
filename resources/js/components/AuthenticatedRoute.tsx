import {
    useEffect,
    type ComponentType,
    type FC,
    type ReactNode,
} from "react";
import { Redirect, Route, type RouteProps } from "wouter";
import { useSession } from "@/hooks/useSession";
import {
    registerPrivateRoutePattern,
    unregisterPrivateRoutePattern,
} from "@/lib/privateRoutes";

type LayoutComponent = ComponentType<{ children: ReactNode }>;
type PageComponent = ComponentType;

interface AuthenticatedRouteBaseProps {
    component: PageComponent;
    fallback?: ReactNode;
    layouts?: LayoutComponent[];
    path: string | string[];
}

type AuthenticatedRouteProps =
    Omit<RouteProps, "children" | "component" | "path"> &
    AuthenticatedRouteBaseProps;

export const AuthenticatedRoute: FC<AuthenticatedRouteProps> = ({
    fallback = null,
    layouts = [],
    component,
    ...props
}) => {
    const { status } = useSession();
    const Page = component;

    useEffect(() => {
        const privateRoutePatterns = Array.isArray(props.path)
            ? props.path
            : [props.path];

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
    const routePath = Array.isArray(props.path) ? props.path[0] : props.path;

    return (
        <Route {...props} path={routePath}>
            {status === "authenticated"
                ? renderedPage
                : status === "checking"
                    ? fallback
                    : <Redirect to="/signin" />}
        </Route>
    );
};
