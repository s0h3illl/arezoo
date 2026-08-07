/**
 * A once-a-day reading of the platform's headline numbers, mirroring
 * `App\Actions\Admin\DashboardSnapshot`. `computed_at` is the one moment all
 * three totals are true of — never of whichever total a later read recalculated.
 */
export type DashboardSnapshot = {
    users_count: number;
    wishes_count: number;
    raised_amount: number;
    computed_at: string;
};
