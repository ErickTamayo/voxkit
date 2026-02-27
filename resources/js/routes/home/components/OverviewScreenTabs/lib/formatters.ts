const WHOLE_NUMBER_TOLERANCE = 0.01;

export function formatOverviewPercentage(value: number): string {
    if (Math.abs(value - Math.round(value)) < WHOLE_NUMBER_TOLERANCE) {
        return `${Math.round(value)}%`;
    }

    return `${value.toFixed(1)}%`;
}

export function formatOverviewInteger(
    value: number,
    locale: string = "en-US",
): string {
    return new Intl.NumberFormat(locale, {
        maximumFractionDigits: 0,
    }).format(value);
}

export function formatOverviewCurrencyFromCents(
    amountCents: number,
    currency: string,
    locale: string = "en-US",
): string {
    const amount = amountCents / 100;

    return new Intl.NumberFormat(locale, {
        style: "currency",
        currency,
    }).format(amount);
}

export function formatOverviewCompactCurrencyFromCents(
    amountCents: number,
    currency: string,
    locale: string = "en-US",
): string {
    const amount = amountCents / 100;

    try {
        const formatted = new Intl.NumberFormat(locale, {
            style: "currency",
            currency,
            notation: "compact",
            maximumFractionDigits: 1,
        }).format(amount);

        return formatted.replace(/[.,]0(?=[A-Za-z]|$)/, "");
    } catch {
        return formatOverviewCurrencyFromCents(amountCents, currency, locale);
    }
}
