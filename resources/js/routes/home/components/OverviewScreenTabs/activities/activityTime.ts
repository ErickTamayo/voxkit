import { useTranslation } from "react-i18next";
import {
    getOverviewRelativeTimeParts,
    isOverviewTimestampOverdue,
} from "@/routes/home/components/OverviewScreenTabs/lib/relativeTime";

interface OverviewActivityTimeFormatter {
    formatDuration: (timestampMs: number) => string;
    isOverdue: (timestampMs: number) => boolean;
}

const useOverviewActivityTimeFormatter = (): OverviewActivityTimeFormatter => {
    const { t } = useTranslation();

    const formatDuration = (timestampMs: number): string => {
        const { unit, value } = getOverviewRelativeTimeParts(timestampMs);

        return t(unit, { count: value });
    };

    return {
        formatDuration,
        isOverdue: isOverviewTimestampOverdue,
    };
};

export { useOverviewActivityTimeFormatter };
