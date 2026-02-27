export const OVERVIEW_RELATIVE_TIME_UNITS = [
    { unit: "year", seconds: 31_536_000 },
    { unit: "month", seconds: 2_592_000 },
    { unit: "day", seconds: 86_400 },
    { unit: "hour", seconds: 3_600 },
    { unit: "minute", seconds: 60 },
    { unit: "second", seconds: 1 },
] as const;

export type OverviewRelativeTimeUnit =
    (typeof OVERVIEW_RELATIVE_TIME_UNITS)[number]["unit"];

export interface OverviewRelativeTimeParts {
    value: number;
    unit: OverviewRelativeTimeUnit;
    isPast: boolean;
}

function isFiniteTimestampMs(value: number): boolean {
    return Number.isFinite(value);
}

export function getOverviewRelativeTimeParts(
    toMs: number,
    fromMs: number = Date.now(),
): OverviewRelativeTimeParts {
    if (!isFiniteTimestampMs(toMs) || !isFiniteTimestampMs(fromMs)) {
        return {
            value: 0,
            unit: "second",
            isPast: false,
        };
    }

    const deltaMs = toMs - fromMs;
    const isPast = deltaMs < 0;
    const absoluteSeconds = Math.floor(Math.abs(deltaMs) / 1_000);

    for (const candidate of OVERVIEW_RELATIVE_TIME_UNITS) {
        if (absoluteSeconds >= candidate.seconds) {
            return {
                value: Math.ceil(absoluteSeconds / candidate.seconds),
                unit: candidate.unit,
                isPast,
            };
        }
    }

    return {
        value: 0,
        unit: "second",
        isPast,
    };
}

export function isOverviewTimestampOverdue(
    timestampMs: number,
    nowMs: number = Date.now(),
): boolean {
    if (!isFiniteTimestampMs(timestampMs) || !isFiniteTimestampMs(nowMs)) {
        return false;
    }

    return timestampMs < nowMs;
}
