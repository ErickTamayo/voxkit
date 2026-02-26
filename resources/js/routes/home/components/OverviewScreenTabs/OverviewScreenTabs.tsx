import {
    lazy,
    Suspense,
    useEffect,
    useLayoutEffect,
    useRef,
    useState,
    type FC,
    type LazyExoticComponent,
} from "react";
import {
    animate,
    motion,
    useMotionValue,
    useReducedMotion,
    type PanInfo,
} from "motion/react";
import { ErrorBoundary } from "react-error-boundary";
import { useIsDesktopViewport } from "@/hooks/useIsDesktopViewport";
import { cn } from "@/lib/utils";
import { OverviewScreenTabErrorFallback } from "@/routes/home/components/OverviewScreenTabs/OverviewScreenTabErrorFallback";
import { OverviewScreenTabPanelSurface } from "@/routes/home/components/OverviewScreenTabs/OverviewScreenTabPanelSurface";
import { getOverviewScreenTabsSwipeOutcome } from "@/routes/home/components/OverviewScreenTabs/overviewScreenTabsSwipe";
import type {
    OverviewScreenTabDefinition,
    OverviewScreenTabsProps,
} from "@/routes/home/components/OverviewScreenTabs/types";

interface OverviewScreenTabLazyCacheEntry {
    component: LazyExoticComponent<FC>;
    loadScreen: OverviewScreenTabDefinition["loadScreen"];
    retryKey: number;
}

interface OverviewScreenTabIndicatorRect {
    height: number;
    width: number;
    x: number;
    y: number;
}

const OverviewScreenTabs: FC<OverviewScreenTabsProps> = ({
    tabs,
    initialValue,
    onValueChange,
    value,
    className,
}) => {
    const prefersReducedMotion = useReducedMotion();
    const isDesktopViewport = useIsDesktopViewport();
    const tabListRef = useRef<HTMLDivElement | null>(null);
    const tabButtonRefs = useRef<Record<string, HTMLButtonElement | null>>({});
    const panelViewportRef = useRef<HTMLDivElement | null>(null);
    const isTrackDraggingRef = useRef<boolean>(false);
    const trackX = useMotionValue<number>(0);
    const [uncontrolledValue, setUncontrolledValue] = useState<string>(
        () => initialValue ?? tabs[0]?.value ?? "",
    );
    const [mountedTabValues, setMountedTabValues] = useState<Record<string, true>>(
        () => {
            const firstValue = value ?? initialValue ?? tabs[0]?.value;

            return firstValue ? { [firstValue]: true } : {};
        },
    );
    const [tabRetryKeys, setTabRetryKeys] = useState<Record<string, number>>({});
    const [activeIndicatorRect, setActiveIndicatorRect] = useState<OverviewScreenTabIndicatorRect | null>(
        null,
    );
    const [panelViewportWidth, setPanelViewportWidth] = useState<number>(0);
    const lazyScreenCacheRef = useRef<Record<string, OverviewScreenTabLazyCacheEntry>>(
        {},
    );

    const isControlled = value !== undefined;
    const fallbackValue = tabs[0]?.value ?? "";
    const activeValueCandidate = value ?? uncontrolledValue;
    const hasActiveTab = tabs.some((tab) => tab.value === activeValueCandidate);
    const activeValue = hasActiveTab ? activeValueCandidate : fallbackValue;
    const activeIndex = Math.max(0, tabs.findIndex((tab) => tab.value === activeValue));
    const isSwipeEnabled = !isDesktopViewport && tabs.length > 1;
    const activeTrackX = panelViewportWidth > 0 ? -(activeIndex * panelViewportWidth) : 0;
    const firstTab = tabs[0];

    const syncActiveIndicatorRect = (): void => {
        const tabListElement = tabListRef.current;
        const activeTabButton = activeValue
            ? tabButtonRefs.current[activeValue]
            : null;

        if (tabListElement === null || activeTabButton === null) {
            setActiveIndicatorRect(null);

            return;
        }

        const nextRect = {
            x: activeTabButton.offsetLeft,
            y: activeTabButton.offsetTop,
            width: activeTabButton.offsetWidth,
            height: activeTabButton.offsetHeight,
        };

        setActiveIndicatorRect((previous) => {
            if (
                previous !== null
                && previous.x === nextRect.x
                && previous.y === nextRect.y
                && previous.width === nextRect.width
                && previous.height === nextRect.height
            ) {
                return previous;
            }

            return nextRect;
        });
    };

    const syncPanelViewportWidth = (): void => {
        const panelViewportElement = panelViewportRef.current;

        if (panelViewportElement === null) {
            setPanelViewportWidth(0);

            return;
        }

        const nextWidth = panelViewportElement.offsetWidth;

        setPanelViewportWidth((previous) => {
            return previous === nextWidth ? previous : nextWidth;
        });
    };

    const animateTrackToActive = (): void => {
        void animate(trackX, activeTrackX, {
            ...(prefersReducedMotion
                ? { duration: 0 }
                : {
                      type: "spring",
                      stiffness: 420,
                      damping: 38,
                      mass: 0.82,
                  }),
        });
    };

    const handleTabSelect = (nextValue: string): void => {
        if (nextValue === activeValue) {
            return;
        }

        if (!isControlled) {
            setUncontrolledValue(nextValue);
        }

        onValueChange?.(nextValue);
    };

    const handleTrackDragStart = (): void => {
        syncPanelViewportWidth();
        isTrackDraggingRef.current = true;
    };

    const handleTrackDragEnd = (
        _event: MouseEvent | TouchEvent | PointerEvent,
        info: PanInfo,
    ): void => {
        isTrackDraggingRef.current = false;

        if (!isSwipeEnabled || tabs.length <= 1) {
            animateTrackToActive();

            return;
        }

        const swipeOutcome = getOverviewScreenTabsSwipeOutcome({
            deltaX: info.offset.x,
            velocityX: info.velocity.x,
        });
        let nextIndex = activeIndex;

        if (swipeOutcome === "next") {
            nextIndex = Math.min(tabs.length - 1, activeIndex + 1);
        }

        if (swipeOutcome === "prev") {
            nextIndex = Math.max(0, activeIndex - 1);
        }

        if (nextIndex === activeIndex) {
            animateTrackToActive();

            return;
        }

        handleTabSelect(tabs[nextIndex].value);
    };

    const retryTabScreenLoad = (tabValue: string): void => {
        setTabRetryKeys((previous) => ({
            ...previous,
            [tabValue]: (previous[tabValue] ?? 0) + 1,
        }));
    };

    const getLazyScreenComponent = (
        tab: OverviewScreenTabDefinition,
    ): LazyExoticComponent<FC> => {
        const retryKey = tabRetryKeys[tab.value] ?? 0;
        const cachedEntry = lazyScreenCacheRef.current[tab.value];

        if (
            cachedEntry !== undefined
            && cachedEntry.retryKey === retryKey
            && cachedEntry.loadScreen === tab.loadScreen
        ) {
            return cachedEntry.component;
        }

        const component = lazy(tab.loadScreen);

        lazyScreenCacheRef.current[tab.value] = {
            component,
            loadScreen: tab.loadScreen,
            retryKey,
        };

        return component;
    };

    useEffect(() => {
        if (!activeValue) {
            return;
        }

        setMountedTabValues((previous) => {
            if (previous[activeValue] !== undefined) {
                return previous;
            }

            return {
                ...previous,
                [activeValue]: true,
            };
        });
    }, [activeValue]);

    useLayoutEffect(() => {
        syncActiveIndicatorRect();
        syncPanelViewportWidth();
    }, [activeValue, tabs]);

    useEffect(() => {
        if (typeof window === "undefined") {
            return;
        }

        const handleResize = (): void => {
            syncActiveIndicatorRect();
            syncPanelViewportWidth();
        };

        handleResize();
        window.addEventListener("resize", handleResize);

        return () => {
            window.removeEventListener("resize", handleResize);
        };
    }, [activeValue, tabs]);

    useEffect(() => {
        if (panelViewportWidth <= 0) {
            trackX.set(0);

            return;
        }

        if (isTrackDraggingRef.current) {
            return;
        }

        const controls = animate(trackX, activeTrackX, {
            ...(prefersReducedMotion
                ? { duration: 0 }
                : {
                      type: "spring",
                      stiffness: 420,
                      damping: 38,
                      mass: 0.82,
                  }),
        });

        return () => {
            controls.stop();
        };
    }, [
        activeTrackX,
        panelViewportWidth,
        prefersReducedMotion,
        trackX,
    ]);

    if (tabs.length === 0) {
        return (
            <section
                className={cn(
                    "rounded-2xl border border-dashed border-border/80 bg-card/70 p-4 text-sm text-muted-foreground",
                    className,
                )}
            >
                Add at least one tab to render the overview screen tabs.
            </section>
        );
    }

    return (
        <section
            className={cn(
                "flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-border/80 bg-card",
                className,
            )}
        >
            <div
                ref={tabListRef}
                role="tablist"
                aria-label="Overview sections"
                className="border-border/80 relative flex items-center gap-1 border-b p-2"
            >
                {activeIndicatorRect !== null ? (
                    <motion.div
                        aria-hidden="true"
                        className="pointer-events-none absolute bottom-px left-0 rounded-full bg-primary"
                        initial={false}
                        animate={{
                            x: activeIndicatorRect.x + 12,
                            y: 0,
                            width: Math.max(12, activeIndicatorRect.width - 24),
                            height: 2,
                        }}
                        transition={
                            prefersReducedMotion
                                ? { duration: 0 }
                                : {
                                      type: "spring",
                                      stiffness: 540,
                                      damping: 38,
                                      mass: 0.72,
                                  }
                        }
                    />
                ) : null}

                {tabs.map((tab) => {
                    const isSelected = tab.value === activeValue;

                    return (
                        <button
                            ref={(element) => {
                                tabButtonRefs.current[tab.value] = element;
                            }}
                            key={tab.value}
                            type="button"
                            role="tab"
                            id={`overview-tab-${tab.value}`}
                            aria-controls={`overview-panel-${tab.value}`}
                            aria-selected={isSelected}
                            tabIndex={isSelected ? 0 : -1}
                            onClick={() => handleTabSelect(tab.value)}
                            className={cn(
                                "relative z-10 rounded-xl px-3 py-2 text-sm font-medium transition-colors outline-none",
                                "focus-visible:ring-ring focus-visible:ring-2 focus-visible:ring-offset-2",
                                isSelected
                                    ? "text-primary"
                                    : "text-muted-foreground hover:bg-accent/60 hover:text-foreground",
                            )}
                        >
                            {tab.label}
                        </button>
                    );
                })}
            </div>

            <div className="min-h-0 flex-1 p-4">
                <div
                    ref={panelViewportRef}
                    className="relative h-full min-h-0 overflow-hidden"
                >
                    <motion.div
                        className="flex h-full min-h-0 touch-pan-y"
                        style={{ x: trackX }}
                        drag={isSwipeEnabled && panelViewportWidth > 0 ? "x" : false}
                        dragConstraints={
                            panelViewportWidth > 0
                                ? {
                                      left: -Math.max(0, tabs.length - 1) * panelViewportWidth,
                                      right: 0,
                                  }
                                : { left: 0, right: 0 }
                        }
                        dragElastic={0.08}
                        dragMomentum={false}
                        dragDirectionLock
                        onDragStart={handleTrackDragStart}
                        onDragEnd={handleTrackDragEnd}
                    >
                        {tabs.map((tab) => {
                            const isActive = tab.value === activeValue;
                            const isMounted = mountedTabValues[tab.value] !== undefined;
                            const LazyScreen = isMounted
                                ? getLazyScreenComponent(tab)
                                : null;
                            const retryKey = tabRetryKeys[tab.value] ?? 0;
                            const showSelectFirstTabAction = (
                                firstTab !== undefined
                                && firstTab.value !== tab.value
                            );

                            return (
                                <section
                                    key={tab.value}
                                    role="tabpanel"
                                    id={`overview-panel-${tab.value}`}
                                    aria-labelledby={`overview-tab-${tab.value}`}
                                    aria-hidden={!isActive}
                                    inert={!isActive}
                                    className={cn(
                                        "h-full w-full shrink-0",
                                        !isActive && "pointer-events-none",
                                    )}
                                >
                                    {isMounted && LazyScreen !== null ? (
                                        <ErrorBoundary
                                            FallbackComponent={
                                                tab.ErrorFallbackComponent
                                                ?? ((fallbackProps) => (
                                                    <OverviewScreenTabErrorFallback
                                                        {...fallbackProps}
                                                        tabLabel={tab.label}
                                                        firstTabLabel={firstTab?.label ?? "first tab"}
                                                        showSelectFirstTabAction={showSelectFirstTabAction}
                                                        onSelectFirstTab={
                                                            showSelectFirstTabAction && firstTab !== undefined
                                                                ? () => handleTabSelect(firstTab.value)
                                                                : undefined
                                                        }
                                                        onRetry={() => retryTabScreenLoad(tab.value)}
                                                    />
                                                ))
                                            }
                                            resetKeys={[retryKey]}
                                        >
                                            <Suspense
                                                fallback={(
                                                    <OverviewScreenTabPanelSurface>
                                                        {tab.loadingFallback}
                                                    </OverviewScreenTabPanelSurface>
                                                )}
                                            >
                                                <OverviewScreenTabPanelSurface>
                                                    <LazyScreen />
                                                </OverviewScreenTabPanelSurface>
                                            </Suspense>
                                        </ErrorBoundary>
                                    ) : null}
                                </section>
                            );
                        })}
                    </motion.div>
                </div>
            </div>
        </section>
    );
};

export { OverviewScreenTabs };
