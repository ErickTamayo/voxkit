import type { FC, ReactNode } from "react";

interface OverviewScreenTabPanelSurfaceProps {
    children: ReactNode;
}

const OverviewScreenTabPanelSurface: FC<OverviewScreenTabPanelSurfaceProps> = ({
    children,
}) => {
    return (
        <div className="flex h-full min-h-40 flex-col rounded-xl border border-dashed border-border/70 bg-muted/30 p-4">
            {children}
        </div>
    );
};

export { OverviewScreenTabPanelSurface };
