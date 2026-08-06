/**
 * A contribution as the admin panel reads it — one row of the contributions
 * list. `App\Http\Resources\Admin\ContributionResource` is deliberately
 * narrower than the `contributions` table: no message, no visibility, since
 * visibility governs what a user sees, never what moderation can see.
 */
export type ContributionStatus = 'pending' | 'paid';

export type Contribution = {
    id: number;
    wish: {
        id: number;
        title: string;
    };
    contributor: {
        id: number;
        name: string;
    };
    amount: number;
    status: ContributionStatus;
    settled_at: string | null;
    /*
     * Never null: a contribution whose gateway attempt failed no longer exists
     * (ADR-0001), so a contribution always has a payment behind it. Attempts
     * with no contribution live on the payments screen instead.
     */
    payment: {
        id: number;
        transaction_id: string | null;
        reference_id: string | null;
    };
};
