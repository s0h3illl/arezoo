/**
 * A wish as the application reads it — every column of the `wishes` table,
 * mirroring `App\Http\Resources\WishResource`.
 *
 * One shape for the whole model rather than one per screen. A page that wants
 * less narrows with `Pick` where it uses it — see `WishCard.vue`, which takes
 * only the five fields it renders — so a column added to the table reaches
 * every page at once instead of being threaded through a new type each time.
 *
 * The admin panel reads a wish through its own narrower shape, in
 * `@/types/admin`, mirroring the resource namespace on the server.
 */
export type Wish = {
    id: number;
    user_id: number;
    title: string;
    description: string;
    /** A ready URL, or null where the wish has no cover of its own. */
    thumbnail: string | null;
    purchase_link: string;
    price: number;
    /** Paid contributions only, and uncapped: it may pass the price (ADR-0004). */
    received: number;
    created_at: string | null;
    updated_at: string | null;
};
