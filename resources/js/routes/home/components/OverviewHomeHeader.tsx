import type { FC } from "react";
import { Menu, Search } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useUser } from "@/hooks/useUser";

interface OverviewHomeHeaderProps {
    onOpenMenuDrawer: () => void;
    onOpenSearch: () => void;
    onOpenSettings: () => void;
}

const OverviewHomeHeader: FC<OverviewHomeHeaderProps> = ({
    onOpenMenuDrawer,
    onOpenSearch,
    onOpenSettings,
}) => {
    const { user } = useUser();
    console.count("overview-header-render");

    return (
        <header className="border-b border-border/70 bg-background px-4 pb-3 pt-4">
            <div className="flex items-center justify-between gap-3">
                <button
                    type="button"
                    onClick={onOpenSettings}
                    className="flex min-w-0 items-center gap-2 rounded-md text-left outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    aria-label="Open settings"
                >
                    <span className="bg-muted text-foreground border-border/80 inline-flex size-9 items-center justify-center rounded-full border text-sm font-semibold">
                        {user.name.charAt(0).toUpperCase()}
                    </span>
                    <span className="min-w-0">
                        <p className="truncate text-base font-semibold text-foreground">
                            {user.name}
                        </p>
                    </span>
                </button>

                <div className="flex shrink-0 items-center gap-1">
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        aria-label="Open search"
                        onClick={onOpenSearch}
                    >
                        <Search className="size-5" />
                    </Button>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        aria-label="Open menu"
                        onClick={onOpenMenuDrawer}
                    >
                        <Menu className="size-5" />
                    </Button>
                </div>
            </div>
        </header>
    );
};

export { OverviewHomeHeader };
export type { OverviewHomeHeaderProps };
