import type { ComponentType, ReactNode } from "react";
import { Route, type RouteProps } from "wouter";

type LayoutComponent = ComponentType<{ children: ReactNode }>;
type PageComponent = ComponentType;

type RouteWithLayoutProps = Omit<RouteProps, "component" | "children"> & {
    component: PageComponent;
    layouts?: LayoutComponent[];
};

export function RouteWithLayout({
    layouts = [],
    component,
    ...props
}: RouteWithLayoutProps): React.JSX.Element {
    const Page = component;
    const renderedPage = layouts.reduceRight<ReactNode>(
        (content, Layout) => <Layout>{content}</Layout>,
        <Page />,
    );

    return <Route {...props}>{renderedPage}</Route>;
}
