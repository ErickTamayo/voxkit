import { create } from "zustand";

interface OverviewLayoutStore {
    headerHeight: number;
    tabBarHeight: number;
    setHeaderHeight: (height: number) => void;
    setTabBarHeight: (height: number) => void;
}

const useOverviewLayoutStore = create<OverviewLayoutStore>((set) => ({
    headerHeight: 0,
    tabBarHeight: 0,
    setHeaderHeight: (height) => {
        set((state) => {
            if (state.headerHeight === height) {
                return state;
            }

            return { headerHeight: height };
        });
    },
    setTabBarHeight: (height) => {
        set((state) => {
            if (state.tabBarHeight === height) {
                return state;
            }

            return { tabBarHeight: height };
        });
    },
}));

export { useOverviewLayoutStore };
export type { OverviewLayoutStore };
