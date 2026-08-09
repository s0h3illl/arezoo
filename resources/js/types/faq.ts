/**
 * One question the landing page answers, mirroring `App\Models\Faq` as
 * `App\Http\Controllers\HomeController` sends it. `title` is the question and
 * `body` the answer — the column names the table already uses.
 */
export interface Faq {
    id: number;
    title: string;
    body: string;
}
