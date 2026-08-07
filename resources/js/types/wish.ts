/**
 * A wish as the admin panel reads it — one row of the dashboard's glance at what
 * has just been published, mirroring `App\Http\Resources\Admin\WishResource`.
 *
 * Deliberately narrower than the `wishes` table, and deliberately without the
 * amount the wish has received: that is a per-wish aggregate, and a column
 * nobody asked for is not worth a query per row.
 */
export type Wish = {
    id: number;
    title: string;
    owner: {
        id: number;
        name: string;
    };
    price: number;
    created_at: string | null;
};
