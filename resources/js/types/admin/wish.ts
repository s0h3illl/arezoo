/**
 * A wish as the admin panel reads it — one row of the dashboard's glance at what
 * has just been published, mirroring `App\Http\Resources\Admin\WishResource`.
 *
 * It shares a name with the public `Wish` in `@/types` and is told apart by the
 * namespace, exactly as the two resources are on the server. Deliberately its
 * own shape rather than a narrowing of that one: the panel is a separate section
 * with a resource of its own, and the thing it needs that a card does not is the
 * owner's name, which a public payload has no business carrying. It is also
 * without the amount the wish has received — a per-wish aggregate is not worth a
 * query per row for a column the glance never reads.
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
