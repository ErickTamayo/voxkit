import {
    createContext,
    useContext,
    useState,
    type Dispatch,
    type FC,
    type ReactNode,
    type SetStateAction,
} from "react";
import { CompactRange } from "@/graphql/types";

interface OverviewPeriodContextValue {
    selectedRange: CompactRange;
    setSelectedRange: Dispatch<SetStateAction<CompactRange>>;
}

interface OverviewPeriodProviderProps {
    children: ReactNode;
    defaultRange?: CompactRange;
}

const OverviewPeriodContext = createContext<OverviewPeriodContextValue | null>(null);

const OverviewPeriodProvider: FC<OverviewPeriodProviderProps> = ({
    children,
    defaultRange = CompactRange.OneWeek,
}) => {
    const [selectedRange, setSelectedRange] = useState<CompactRange>(defaultRange);

    return (
        <OverviewPeriodContext.Provider value={{ selectedRange, setSelectedRange }}>
            {children}
        </OverviewPeriodContext.Provider>
    );
};

function useOverviewPeriod(): OverviewPeriodContextValue {
    const context = useContext(OverviewPeriodContext);

    if (context === null) {
        throw new Error("useOverviewPeriod must be used within OverviewPeriodProvider.");
    }

    return context;
}

export { OverviewPeriodProvider, useOverviewPeriod };
