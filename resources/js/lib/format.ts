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

/** Every amount in this app is Toman (ADR-0002). */
export function formatToman(amount: number): string {
    return `${amountFormatter.format(amount)} تومان`;
}

/** A day and a time, for moments where the hour matters. */
export function formatMoment(iso: string): string {
    return momentFormatter.format(new Date(iso));
}
