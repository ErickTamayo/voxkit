import type { ComponentType, FC, ReactNode } from "react";
import { Route, type RouteProps } from "wouter";

type LayoutComponent = ComponentType<{ children: ReactNode }>;
type PageComponent = ComponentType;

interface RouteWithLayoutProps
    extends Omit<RouteProps, "children" | "component"> {
    component: PageComponent;
    layouts?: LayoutComponent[];
}

export const RouteWithLayout: FC<RouteWithLayoutProps> = ({
    layouts = [],
    component,
    ...props
}) => {
    const Page = component;
    const renderedPage = layouts.reduceRight<ReactNode>(
        (content, Layout) => <Layout>{content}</Layout>,
        <Page />,
    );

    return <Route {...props}>{renderedPage}</Route>;
};
