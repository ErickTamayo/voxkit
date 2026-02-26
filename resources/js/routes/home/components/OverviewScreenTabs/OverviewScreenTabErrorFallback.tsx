import type { FC } from "react";
import type { FallbackProps } from "react-error-boundary";
import { Button } from "@/components/ui/button";
import { OverviewScreenTabPanelSurface } from "@/routes/home/components/OverviewScreenTabs/OverviewScreenTabPanelSurface";

interface OverviewScreenTabErrorFallbackProps extends FallbackProps {
    onRetry: () => void;
    onSelectFirstTab?: () => void;
    showSelectFirstTabAction: boolean;
    firstTabLabel: string;
    tabLabel: string;
}

const OverviewScreenTabErrorFallback: FC<OverviewScreenTabErrorFallbackProps> = ({
    resetErrorBoundary,
    onRetry,
    onSelectFirstTab,
    showSelectFirstTabAction,
    firstTabLabel,
    tabLabel,
}) => {
    return (
        <OverviewScreenTabPanelSurface>
            <div className="space-y-3">
                <p className="text-sm font-medium text-destructive">
                    Could not load {tabLabel}.
                </p>
                <p className="text-sm text-muted-foreground">
                    Try again or switch to another overview tab.
                </p>
                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        onClick={() => {
                            onRetry();
                            resetErrorBoundary();
                        }}
                    >
                        Try again
                    </Button>

                    {showSelectFirstTabAction && onSelectFirstTab !== undefined ? (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onSelectFirstTab()}
                        >
                            Open {firstTabLabel}
                        </Button>
                    ) : null}
                </div>
            </div>
        </OverviewScreenTabPanelSurface>
    );
};

export { OverviewScreenTabErrorFallback };
