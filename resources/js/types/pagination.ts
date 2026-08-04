/**
 * The shape Laravel's length-aware paginator serialises to, narrowed to the
 * fields the app actually reads. `data` is the page of rows itself.
 */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
