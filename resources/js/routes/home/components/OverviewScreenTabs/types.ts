import type { ComponentType, FC, ReactNode } from "react";
import type { FallbackProps } from "react-error-boundary";

export interface OverviewScreenTabModule {
    default: FC;
}

export interface OverviewScreenTabDefinition {
    label: string;
    loadScreen: () => Promise<OverviewScreenTabModule>;
    loadingFallback: ReactNode;
    value: string;
    ErrorFallbackComponent?: ComponentType<FallbackProps>;
}

export interface OverviewScreenTabsProps {
    tabs: OverviewScreenTabDefinition[];
    initialValue?: string;
    onValueChange?: (value: string) => void;
    onSwipeStart?: () => void;
    onTabPress?: (value: string) => void;
    value?: string;
    className?: string;
}
