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
};
