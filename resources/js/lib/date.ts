/**
 * Formats a Date as YYYY-MM-DD using its LOCAL calendar fields.
 *
 * `date.toISOString().slice(0, 10)` looks equivalent but isn't: it reads
 * the UTC calendar day, which differs from the local one for roughly a
 * third of the day in any positive-UTC-offset timezone (e.g. Europe/Kyiv).
 * Round-tripped through `new Date(`${d}T00:00:00`)` (local-time parsing)
 * and `.setDate()` (local-time arithmetic) elsewhere, that mismatch makes
 * a "shift by 1 day" silently become "shift by 0" or "shift by 2" — this
 * is the actual formatter to use anywhere a day is built from date-local
 * arithmetic (day switchers, period pickers, etc).
 */
export function toDateInput(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}
