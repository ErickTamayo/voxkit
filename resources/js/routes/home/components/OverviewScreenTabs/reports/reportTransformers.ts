import { CompactRange, ProjectCategory } from "@/graphql/types";
import { formatOverviewPercentage } from "@/routes/home/components/OverviewScreenTabs/lib/formatters";

interface OverviewBarDatum {
    color: string;
    label: string;
    value: number;
}

interface OverviewChartPointInput {
    timestamp: number;
    value: number;
}

interface OverviewLineDatum {
    x: number;
    y: number;
}

interface OverviewRevenueBySourceInput {
    percentage_of_total: number;
    source_name: string;
}

interface OverviewRevenueByCategoryInput {
    category: ProjectCategory;
    percentage_of_total: number;
}

const OVERVIEW_CATEGORY_LABELS: Record<ProjectCategory, string> = {
    [ProjectCategory.Animation]: "Animation",
    [ProjectCategory.Announcement]: "Announcement",
    [ProjectCategory.Audiobook]: "Audiobook",
    [ProjectCategory.Coaching]: "Coaching",
    [ProjectCategory.Commercial]: "Commercial",
    [ProjectCategory.Corporate]: "Corporate",
    [ProjectCategory.Documentary]: "Documentary",
    [ProjectCategory.Dubbing]: "Dubbing",
    [ProjectCategory.Elearning]: "eLearning",
    [ProjectCategory.Explainer]: "Explainer",
    [ProjectCategory.Film]: "Film",
    [ProjectCategory.Ivr]: "IVR",
    [ProjectCategory.Meditation]: "Meditation",
    [ProjectCategory.Narration]: "Narration",
    [ProjectCategory.Other]: "Other",
    [ProjectCategory.Podcast]: "Podcast",
    [ProjectCategory.Promo]: "Promo",
    [ProjectCategory.RadioImaging]: "Radio imaging",
    [ProjectCategory.Trailer]: "Trailer",
    [ProjectCategory.TvSeries]: "TV series",
    [ProjectCategory.Unknown]: "Unknown",
    [ProjectCategory.VideoGame]: "Video game",
};

function clamp(value: number, min = 0, max = 255): number {
    return Math.min(max, Math.max(min, value));
}

function hexToRgb(hexColor: string): { b: number; g: number; r: number } | null {
    const normalized = hexColor.replace("#", "");

    if (normalized.length !== 6) {
        return null;
    }

    const r = Number.parseInt(normalized.slice(0, 2), 16);
    const g = Number.parseInt(normalized.slice(2, 4), 16);
    const b = Number.parseInt(normalized.slice(4, 6), 16);

    if ([r, g, b].some((channel) => Number.isNaN(channel))) {
        return null;
    }

    return { r, g, b };
}

function rgbToHex(r: number, g: number, b: number): string {
    return `#${clamp(r).toString(16).padStart(2, "0")}${clamp(g)
        .toString(16)
        .padStart(2, "0")}${clamp(b).toString(16).padStart(2, "0")}`;
}

function mixOverviewColorWithWhite(hexColor: string, ratio: number): string {
    const rgb = hexToRgb(hexColor);

    if (rgb === null) {
        return hexColor;
    }

    const safeRatio = Math.min(1, Math.max(0, ratio));

    return rgbToHex(
        Math.round(rgb.r + (255 - rgb.r) * safeRatio),
        Math.round(rgb.g + (255 - rgb.g) * safeRatio),
        Math.round(rgb.b + (255 - rgb.b) * safeRatio),
    );
}

function formatOverviewChartLabel(
    timestampMs: number | string,
    range: CompactRange,
    options?: { includeYear?: boolean; locale?: string },
): string {
    const locale = options?.locale ?? "en-US";
    const includeYear = options?.includeYear ?? false;
    const timestamp = typeof timestampMs === "number" ? timestampMs : Number(timestampMs);

    if (Number.isNaN(timestamp)) {
        return String(timestampMs);
    }

    const baseOptions = (
        range === CompactRange.AllTime
        || range === CompactRange.OneYear
        || range === CompactRange.YearToDate
    )
        ? { month: "short" as const }
        : { month: "short" as const, day: "numeric" as const };

    const formatOptions = includeYear
        ? { ...baseOptions, year: "numeric" as const }
        : baseOptions;

    return new Intl.DateTimeFormat(locale, {
        ...formatOptions,
        timeZone: "UTC",
    }).format(timestamp);
}

function formatOverviewChartDisplayRange(input: {
    period: CompactRange;
    periodEnd: number;
    periodStart: number;
}): string {
    return `${formatOverviewChartLabel(input.periodStart, input.period)} - ${formatOverviewChartLabel(
        input.periodEnd,
        input.period,
    )}`;
}

function getOverviewProjectCategoryLabel(category: ProjectCategory): string {
    return OVERVIEW_CATEGORY_LABELS[category] ?? "Unknown";
}

function normalizeOverviewPercentages(values: number[]): number {
    const total = values.reduce((sum, value) => sum + value, 0);

    return total <= 1.5 ? 100 : 1;
}

function toOverviewLineData(points: OverviewChartPointInput[]): OverviewLineDatum[] {
    return points.map((point) => ({
        x: point.timestamp,
        y: point.value,
    }));
}

function toOverviewRevenueBySourceBars(input: {
    baseColor: string;
    entries: OverviewRevenueBySourceInput[];
}): OverviewBarDatum[] {
    const sortedEntries = [...input.entries]
        .filter((entry) => entry.percentage_of_total > 0)
        .sort((a, b) => b.percentage_of_total - a.percentage_of_total);
    const scale = normalizeOverviewPercentages(
        sortedEntries.map((entry) => entry.percentage_of_total),
    );
    const tintSteps = Math.max(1, sortedEntries.length);

    return sortedEntries.map((entry, index) => {
        const sourceLabel = entry.source_name.trim() || "Unknown";
        const value = entry.percentage_of_total * scale;
        const tintRatio = tintSteps <= 1 ? 0 : index / (tintSteps - 1);

        return {
            label: `${sourceLabel} (${formatOverviewPercentage(value)})`,
            value,
            color: mixOverviewColorWithWhite(input.baseColor, tintRatio * 0.55),
        };
    });
}

function toOverviewRevenueByCategoryBars(input: {
    baseColor: string;
    entries: OverviewRevenueByCategoryInput[];
    resolveCategoryLabel?: (category: ProjectCategory) => string;
}): OverviewBarDatum[] {
    const resolveCategoryLabel = input.resolveCategoryLabel ?? getOverviewProjectCategoryLabel;
    const sortedEntries = [...input.entries]
        .filter((entry) => entry.percentage_of_total > 0)
        .sort((a, b) => b.percentage_of_total - a.percentage_of_total);
    const scale = normalizeOverviewPercentages(
        sortedEntries.map((entry) => entry.percentage_of_total),
    );
    const tintSteps = Math.max(1, sortedEntries.length);

    return sortedEntries.map((entry, index) => {
        const label = resolveCategoryLabel(entry.category);
        const value = entry.percentage_of_total * scale;
        const tintRatio = tintSteps <= 1 ? 0 : index / (tintSteps - 1);

        return {
            label: `${label} (${formatOverviewPercentage(value)})`,
            value,
            color: mixOverviewColorWithWhite(input.baseColor, tintRatio * 0.55),
        };
    });
}

function getOverviewBarMaxValue(items: Array<{ value: number }>): number {
    return items.reduce((currentMax, item) => Math.max(currentMax, item.value), 0);
}

export {
    formatOverviewChartDisplayRange,
    formatOverviewChartLabel,
    getOverviewBarMaxValue,
    getOverviewProjectCategoryLabel,
    mixOverviewColorWithWhite,
    toOverviewLineData,
    toOverviewRevenueByCategoryBars,
    toOverviewRevenueBySourceBars,
};
export type {
    OverviewBarDatum,
    OverviewChartPointInput,
    OverviewLineDatum,
    OverviewRevenueByCategoryInput,
    OverviewRevenueBySourceInput,
};
