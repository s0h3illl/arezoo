import type { WithdrawalStatus } from '../withdrawal';

/**
 * A withdrawal as the admin queue reads it — one row of the queue, mirroring
 * `App\Http\Resources\Admin\WithdrawalResource`. `owner` is a bare name: no row
 * links to a user detail view.
 */
export type Withdrawal = {
    id: number;
    owner: { name: string };
    amount: number;
    fee: number;
    transfer: number;
    sheba: string;
    status: WithdrawalStatus;
    requested_at: string;
    decided_at: string | null;
};
