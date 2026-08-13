/**
 * How the app writes money and moments for a Persian reader.
 *
 * `fa-IR` gives the Jalali calendar and Persian digits the reader actually
 * keeps, and both come with the browser — a date library would be a dependency
 * for two lines. Callers put the machine-readable original on a `<time>`
 * element beside whatever these return.
 */
const amountFormatter = new Intl.NumberFormat('fa-IR');

const momentFormatter = new Intl.DateTimeFormat('fa-IR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: 'numeric',
    minute: 'numeric',
});

const shareFormatter = new Intl.NumberFormat('fa-IR', { style: 'percent' });

/** Every amount in this app is Toman (ADR-0002). */
export function formatToman(amount: number): string {
    return `${amountFormatter.format(amount)} تومان`;
}

/**
 * A bare number: a count, or an amount whose unit is written once beside it
 * rather than on every figure in a row.
 */
export function formatNumber(value: number): string {
    return amountFormatter.format(value);
}

/**
 * What part of a whole something is, as a fraction.
 *
 * Deliberately uncapped: a wish given more than its price is 1.4 of it, not 1
 * (ADR-0004). A whole of zero has no share to report, and is answered with none
 * rather than with infinity.
 */
export function shareOf(part: number, whole: number): number {
    return whole > 0 ? part / whole : 0;
}

/** The same share, written as a whole percent — «۱۴۰٪» reads as honestly as 1.4. */
export function formatShare(part: number, whole: number): string {
    return shareFormatter.format(shareOf(part, whole));
}

/** A day and a time, for moments where the hour matters. */
export function formatMoment(iso: string): string {
    return momentFormatter.format(new Date(iso));
}
