/**
 * The shape a paginated `ResourceCollection` serialises to, narrowed to the
 * fields the app actually reads. `data` is the page of rows itself; the counts
 * live under `meta` and the steps between pages under `links`.
 */
export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
};
